<?php
namespace Gt\Fetch;

use Gt\Curl\JsonDecodeException;
use Gt\Http\Header\ResponseHeaders;
use Gt\Http\Response;
use Gt\Http\StatusCode;
use Psr\Http\Message\UriInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use stdClass;

/**
 * @property-read ResponseHeaders $headers
 * @property-read bool $ok
 * @property-read bool $redirected
 * @property-read int $status
 * @property-read string $statusText
 * @property-read string $type
 * @property-read UriInterface $uri
 * @property-read UriInterface $url
 */
class BodyResponse extends Response {
	/** @var Deferred */
	protected $deferred;
	protected $deferredStatus;
	/** @var LoopInterface */
	protected $loop;

	public function arrayBuffer():Promise {
	}

	public function blob():Promise {
	}

	public function formData():Promise {
	}

	public function json(int $depth = 512, int $options = 0):Promise {
		$deferred = new Deferred();

		$json = json_decode(
			$this->getBody(),
			false,
			$depth,
			$options
		);
		if(is_null($json)) {
			$errorMessage = json_last_error_msg();
			throw new JsonDecodeException($errorMessage);
		}

		$deferred->resolve($json);

		return $deferred->promise();
	}

	public function text():Promise {
		$newPromise = new Promise($this->loop);

		$deferredPromise = $this->deferred->promise();
		$deferredPromise->then(function(string $resolvedValue)
		use($newPromise) {
			$newPromise->resolve($resolvedValue);
		});

		return $newPromise;
	}

	public function startDeferredResponse(LoopInterface $loop):Deferred {
		$this->loop = $loop;
		$this->deferred = new Deferred();
		$this->deferredStatus = Promise::PENDING;
		return $this->deferred;
	}

	public function endDeferredResponse():void {
		$position = $this->stream->tell();
		$this->stream->rewind();
		$contents = $this->stream->getContents();
		$this->stream->seek($position);

		$this->deferred->resolve($contents);
		$this->deferredStatus = Promise::FULFILLED;
	}

	public function deferredResponseStatus():string {
		return $this->deferredStatus;
	}

	public function __get(string $name) {
		switch($name) {
		case "headers":
			return $this->getHeaders();
			break;

		case "ok":
			return ($this->statusCode >= 200
				&& $this->statusCode < 300);

		case "redirected":
			break;

		case "status":
			return $this->getStatusCode();

		case "statusText":
			return StatusCode::REASON_PHRASE[$this->status];

		case "type":
			// TODO: What exactly is this property for?
			break;

		case "uri":
		case "url":
			// TODO: How do we get the URI from the request?
			break;
		}
	}
}