<?php
namespace Gt\Fetch;

use Gt\Async\Loop;
use Gt\Async\Timer\PeriodicTimer;
use Gt\Async\Timer\Timer;
use Gt\Curl\Curl;
use Gt\Curl\CurlMulti;
use Gt\Fetch\Response\BodyResponse;
use Gt\Http\Uri;
use Gt\Promise\Deferred;
use Http\Promise\Promise as HttpPromise;
use Http\Client\HttpClient;
use Http\Client\HttpAsyncClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class Http implements HttpClient, HttpAsyncClient {
	const USER_AGENT = "PhpGt/Fetch";

	protected float $interval;
	protected RequestResolver $requestResolver;
	/** @var array<int, mixed> cURL options */
	protected array $curlOptions = [
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_USERAGENT => self::USER_AGENT,
	];
	protected Loop $loop;
	protected Timer $timer;

	public function __construct(
		array $curlOptions = [],
		float $interval = 0.01,
		string $curlClass = Curl::class,
		string $curlMultiClass = CurlMulti::class
	) {
		$this->curlOptions = $curlOptions + $this->curlOptions;
		$this->interval = $interval;

		$this->loop = new Loop();
		$this->requestResolver = new RequestResolver(
			$this->loop,
			$curlClass,
			$curlMultiClass
		);
		$this->timer = new PeriodicTimer($this->interval, true);
		$this->timer->addCallback($this->requestResolver->tick(...));
		$this->loop->addTimer($this->timer);
	}

	/**
	 * @interface HttpClient
	 */
	public function sendRequest(
		RequestInterface $request
	):ResponseInterface {
		$returnValue = null;

		$this->fetch($request)
		->then(function(BodyResponse $response) use(&$returnValue) {
			$returnValue = $response;
		});

		$this->wait();

		/** @var BodyResponse $returnValue */
		return $returnValue;
	}

	/**
	 * @interface HttpAsyncClient
	 */
	public function sendAsyncRequest(
		RequestInterface $request
	):HttpPromise {
		return $this->fetch($request);
	}

	/**
	 * Creates a new Deferred object to perform the resolution of the request and
	 * returns a PSR-7 compatible promise that represents the result of the response
	 *
	 * @param array<string, mixed> $init
	 */
	public function fetch(string|UriInterface|RequestInterface $input, array $init = []):HttpPromise {
		$deferred = new Deferred();
		$deferredPromise = $deferred->getPromise();

		$uri = $this->ensureUriInterface($input);

		$curlOptBuilder = new CurlOptBuilder($input, $init);
		$curlOptArray = $this->curlOptions;

		foreach($curlOptBuilder->asCurlOptArray() as $key => $value) {
			$curlOptArray[$key] = $value;
		}

		$this->requestResolver->add(
			$uri,
			$curlOptArray,
			$deferred,
			$curlOptBuilder->getIntegrity(),
			$curlOptBuilder->getSignal()
		);

		$newPromise = new Promise($this->loop);

		$deferredPromise->then(function(ResponseInterface $response)
		use($newPromise) {
			$newPromise->resolve($response);
		});

		return $newPromise;
	}

	public function getCurlOptions():array {
		return $this->curlOptions;
	}

	public function ensureUriInterface(string|UriInterface $input):UriInterface {
		if($input instanceof RequestInterface) {
			$uri = $input->getUri();
		}
		else {
			$uri = new Uri($input);
		}

		return $uri;
	}

	/**
	 * Executes all promises in parallel, returning only when all promises
	 * have been fulfilled.
	 */
	public function wait():void {
		$this->loop->run();
	}

	/**
	 * Begins execution of all promises, returning its own Promise that will
	 * resolve when the last HTTP request is fully resolved.
	 */
	public function all():HttpPromise {
		$start = microtime(true);
		$this->wait();
		$end = microtime(true);

		$promise = new Promise($this->loop);
		$promise->resolve($end - $start);
		return $promise;
	}
}
