<?php
namespace Gt\Fetch\Response;

use Gt\Async\Loop;
use Gt\Curl\Curl;
use Gt\Curl\CurlInterface;
use Gt\Fetch\IntegrityMismatchException;
use Gt\Fetch\InvalidIntegrityAlgorithmException;
use Gt\Fetch\Promise;
use Gt\Http\Header\ResponseHeaders;
use Gt\Http\Response;
use Gt\Http\StatusCode;
use Gt\Json\JsonDecodeException;
use Gt\Json\JsonObjectBuilder;
use Gt\Promise\Deferred;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use SplFixedArray;

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
	protected Deferred $deferred;
	protected string $deferredStatus;
	protected Loop $loop;
	protected Curl $curl;

	public function __get(string $name) {
		switch($name) {
		case "headers":
			return $this->getResponseHeaders();

		case "ok":
			return ($this->getStatusCode() >= 200
				&& $this->getStatusCode() < 300);

		case "redirected":
			$redirectCount = $this->curl->getInfo(
				CURLINFO_REDIRECT_COUNT
			);
			return $redirectCount > 0;

		case "status":
			return $this->getStatusCode();

		case "statusText":
			return StatusCode::REASON_PHRASE[$this->status] ?? null;

		case "uri":
		case "url":
			return $this->curl->getInfo(CURLINFO_EFFECTIVE_URL);
		}

		throw new RuntimeException("Undefined property: $name");
	}

	public function arrayBuffer():Promise {
		$newPromise = new Promise($this->loop);

		$deferredPromise = $this->deferred->promise();
		$deferredPromise->then(function(string $resolvedValue)
		use($newPromise) {
			$bytes = strlen($resolvedValue);
			$arrayBuffer = new SplFixedArray($bytes);
			for($i = 0; $i < $bytes; $i++) {
				$arrayBuffer->offsetSet($i, ord($resolvedValue[$i]));
			}

			$newPromise->resolve($arrayBuffer);
		});

		return $newPromise;
	}

	public function blob():Promise {
		$newPromise = new Promise($this->loop);

		$type = $this->getHeaderLine("Content-Type");

		$deferredPromise = $this->deferred->promise();
		$deferredPromise->then(function(string $resolvedValue)
		use($newPromise, $type) {
			$newPromise->resolve(
				new Blob($resolvedValue, [
					"type" => $type,
				])
			);
		});

		return $newPromise;
	}

	public function formData():Promise {
		$newPromise = new Promise($this->loop);

		$deferredPromise = $this->deferred->promise();
		$deferredPromise->then(function(string $resolvedValue)
		use($newPromise) {
			parse_str($resolvedValue, $bodyData);
			$newPromise->resolve($bodyData);
		});

		return $newPromise;
	}

	public function json(int $depth = 512, int $options = 0):Promise {
		$newPromise = new Promise($this->loop);

		$deferredPromise = $this->deferred->getPromise();
		$deferredPromise->then(function(string $resolvedValue)
		use($newPromise, $depth, $options) {
			$builder = new JsonObjectBuilder();
			try {
				$json = $builder->fromJsonString($resolvedValue);
				$newPromise->resolve($json);
			}
			catch(JsonDecodeException $exception) {
				$newPromise->reject($exception);
			}
		});

		return $newPromise;
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

	public function startDeferredResponse(
		Loop $loop,
		CurlInterface $curl
	):Deferred {
		$this->loop = $loop;
		$this->deferred = new Deferred();
		$this->deferredStatus = Promise::PENDING;
		$this->curl = $curl;
		return $this->deferred;
	}

	public function endDeferredResponse(string $integrity = null):void {
		$position = $this->stream->tell();
		$this->stream->rewind();
		$contents = $this->stream->getContents();
		$this->stream->seek($position);

		$this->checkIntegrity($integrity, $contents);

		$this->deferred->resolve($contents);
		$this->deferredStatus = Promise::FULFILLED;
	}

	public function deferredResponseStatus():?string {
		return $this->deferredStatus;
	}

	protected function checkIntegrity(?string $integrity, $contents) {
		if(is_null($integrity)) {
			return;
		}

		[$algo, $hash] = explode("-", $integrity);

		$availableAlgos = hash_algos();
		if(!in_array($algo, $availableAlgos)) {
			throw new InvalidIntegrityAlgorithmException($algo);
		}

		$hashedContents = hash($algo, $contents);

		if($hashedContents !== $hash) {
			throw new IntegrityMismatchException();
		}
	}
}
