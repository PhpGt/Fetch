<?php
namespace Gt\Fetch\Response;

use Gt\Async\Loop;
use Gt\Curl\Curl;
use Gt\Curl\CurlInterface;
use Gt\Fetch\IntegrityMismatchException;
use Gt\Fetch\InvalidIntegrityAlgorithmException;
use Gt\Http\Header\ResponseHeaders;
use Gt\Http\Response;
use Gt\Http\StatusCode;
use Gt\Json\JsonDecodeException;
use Gt\Json\JsonObjectBuilder;
use Gt\Promise\Deferred;
use Gt\Promise\Promise;
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
		$newDeferred = new Deferred();
		$newPromise = $newDeferred->getPromise();

		$deferredPromise = $this->deferred->getPromise();
		$deferredPromise->then(function(string $resolvedValue)
		use($newDeferred) {
			$bytes = strlen($resolvedValue);
			$arrayBuffer = new SplFixedArray($bytes);
			for($i = 0; $i < $bytes; $i++) {
				$arrayBuffer->offsetSet($i, ord($resolvedValue[$i]));
			}

			$newDeferred->resolve($arrayBuffer);
		});

		return $newPromise;
	}

	public function blob():Promise {
		return $this->text();
	}

	public function formData():Promise {
		$newDeferred = new Deferred();
		$newPromise = $newDeferred->getPromise();

		$deferredPromise = $this->deferred->getPromise();
		$deferredPromise->then(function(string $resolvedValue)
		use($newDeferred) {
			parse_str($resolvedValue, $bodyData);
			$newDeferred->resolve($bodyData);
		});

		return $newPromise;
	}

	public function json(int $depth = 512, int $options = 0):Promise {
		$newDeferred = new Deferred();
		$newPromise = $newDeferred->getPromise();

		$deferredPromise = $this->deferred->getPromise();
		$deferredPromise->then(function(string $resolvedValue)
		use($newDeferred, $depth, $options) {
			$builder = new JsonObjectBuilder();
			try {
				$json = $builder->fromJsonString($resolvedValue);
				$newDeferred->resolve($json);
			}
			catch(JsonDecodeException $exception) {
				$newDeferred->reject($exception);
			}
		});

		return $newPromise;
	}

	public function text():Promise {
		$newDeferred = new Deferred();
		$newPromise = $newDeferred->getPromise();

		$this->deferred->getPromise()
			->then(function(string $html)use($newDeferred) {
				$newDeferred->resolve($html);
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
		return $this->deferredStatus ?? null;
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
