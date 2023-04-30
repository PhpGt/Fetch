<?php /** @noinspection PhpUnusedPrivateMethodInspection */
namespace Gt\Fetch\Response;

use Gt\Async\Loop;
use Gt\Curl\Curl;
use Gt\Curl\CurlInterface;
use Gt\Fetch\IntegrityMismatchException;
use Gt\Fetch\InvalidIntegrityAlgorithmException;
use Gt\Http\Header\ResponseHeaders;
use Gt\Http\Request;
use Gt\Http\Response;
use Gt\Http\StatusCode;
use Gt\Json\JsonDecodeException;
use Gt\Json\JsonObjectBuilder;
use Gt\Promise\Deferred;
use Gt\Promise\Promise;
use Gt\Promise\PromiseState;
use Gt\PropFunc\MagicProp;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use SplFixedArray;
use Throwable;

/**
 * @property-read ResponseHeaders $headers
 * @property-read bool $ok
 * @property-read bool $redirected
 * @property-read int $status
 * @property-read string $statusText
 * @property-read string $type
 * @property-read UriInterface $uri
 * @property-read UriInterface $url
 * @SuppressWarnings("UnusedPrivateMethod")
 */
class FetchResponse extends Response {
	use MagicProp;

	protected Deferred $deferred;
	protected PromiseState $deferredStatus;
	protected Loop $loop;
	protected CurlInterface $curl;

	public function __construct(
		?int $statusCode = null,
		ResponseHeaders $headers = null,
		?Request $request = null,
	) {
		$this->deferredStatus = PromiseState::PENDING;

		parent::__construct(
			$statusCode,
			$headers,
			$request
		);
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_headers():ResponseHeaders {
		return $this->getResponseHeaders();
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_ok():bool {
		return ($this->getStatusCode() >= 200
			&& $this->getStatusCode() < 300);
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_redirected():bool {
		$redirectCount = $this->curl->getInfo(
			CURLINFO_REDIRECT_COUNT
		);
		return $redirectCount > 0;
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_status():int {
		return $this->getStatusCode();
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_statusText():?string {
		return StatusCode::REASON_PHRASE[$this->status] ?? null;
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_uri():string {
		return $this->curl->getInfo(CURLINFO_EFFECTIVE_URL);
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_url():string {
		return $this->uri;
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_type():string {
		return $this->headers->get("content-type")?->getValue() ?? "";
	}

	public function arrayBuffer():Promise {
		$promise = $this->deferred->getPromise();
		$promise->then(function(string $responseText) {
			$bytes = strlen($responseText);
			$arrayBuffer = new SplFixedArray($bytes);
			for($i = 0; $i < $bytes; $i++) {
				$arrayBuffer->offsetSet($i, ord($responseText[$i]));
			}

			$this->deferred->resolve($arrayBuffer);
		});

		return $promise;
	}

	public function blob():Promise {
		$promise = $this->deferred->getPromise();
		$promise->then(function(string $responseText) {
			$this->deferred->resolve(new Blob($responseText));
		});

		return $promise;
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
		$promise = $this->deferred->getPromise();
		$promise->then(function(string $responseText)use($depth, $options) {
			$builder = new JsonObjectBuilder($depth, $options);
			$json = $builder->fromJsonString($responseText);
			$this->deferred->resolve($json);
		});

		return $promise;
	}

	public function text():Promise {
		$promise = $this->deferred->getPromise();
		$promise->then(function(string $responseText) {
			$this->deferred->resolve($responseText);
		});

		return $promise;
	}

	public function startDeferredResponse(
		Loop $loop,
		CurlInterface $curl
	):Deferred {
		$this->loop = $loop;
		$this->deferred = new Deferred();
		$this->deferredStatus = PromiseState::PENDING;
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
		$this->deferredStatus = PromiseState::RESOLVED;
	}

	public function deferredResponseStatus():PromiseState {
		return $this->deferredStatus;
	}

	protected function checkIntegrity(?string $integrity, string $contents):void {
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
