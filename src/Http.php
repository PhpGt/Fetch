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
use Gt\Promise\Promise;
use Http\Promise\Promise as HttpPromiseInterface;
use Http\Client\HttpClient;
use Http\Client\HttpAsyncClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class Http implements HttpClient, HttpAsyncClient {
	const USER_AGENT = "PhpGt/Fetch";
	const DEFAULT_CURL_OPTIONS = [
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_USERAGENT => self::USER_AGENT,
	];

	/** @var array<int, mixed> */
	public readonly array $curlOptions;
	private readonly float $interval;
	private RequestResolver $requestResolver;

	private Loop $loop;
	private Timer $timer;

	/**
	 * @param array<string, int|string> $curlOptions
	 */
	public function __construct(
		array $curlOptions = [],
		float $interval = 0.01,
		string $curlClass = Curl::class,
		string $curlMultiClass = CurlMulti::class
	) {
		$this->curlOptions = $curlOptions + self::DEFAULT_CURL_OPTIONS;
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

	/** @interface HttpClient */
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

	/** @interface HttpAsyncClient */
	public function sendAsyncRequest(
		RequestInterface $request
	):HttpPromiseInterface {
		return $this->fetch($request);
	}

	/**
	 * Creates a new Deferred object to perform the resolution of the request and
	 * returns a PSR-7 compatible promise that represents the result of the response
	 *
	 * @param array<int|string, mixed> $init
	 */
	public function fetch(
		string|UriInterface|RequestInterface $input,
		array $init = []
	):Promise {
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

		return $deferredPromise;
	}

	public function ensureUriInterface(
		string|UriInterface|RequestInterface $input
	):UriInterface {
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
	public function all():HttpPromiseInterface {
		$start = microtime(true);
		$this->wait();
		$end = microtime(true);

		$deferred = new Deferred();
		$this->loop->addDeferredToTimer($deferred);
		$promise = $deferred->getPromise();
		$deferred->resolve($end - $start);
		return $promise;
	}
}
