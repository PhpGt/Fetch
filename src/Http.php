<?php
namespace Gt\Fetch;

use Gt\Http\Uri;
use Http\Promise\Promise as HttpPromise;
use Http\Client\HttpClient;
use Http\Client\HttpAsyncClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;

class Http implements HttpClient, HttpAsyncClient {
	const REFERRER = "PhpGt/Fetch";

	/** @var float */
	protected $interval;
	/** @var RequestResolver */
	protected $requestResolver;
	/** @var array cURL options */
	protected $options = [
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_REFERER => self::REFERRER,
	];
	/** @var LoopInterface */
	protected $loop;
	/** @var TimerInterface */
	protected $timer;

	public function __construct(
		array $options = [],
		float $interval = 0.01
	) {
		$this->options = $options + $this->options;
		$this->interval = $interval;

		$this->loop = LoopFactory::create();
		$this->requestResolver = new RequestResolver($this->loop);
		$this->timer = $this->loop->addPeriodicTimer(
			$this->interval,
			[$this->requestResolver, "tick"]
		);
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
	 * Long-hand for the GlobalFetchHelper get, head, post, etc.
	 *
	 * @param string|UriInterface|RequestInterface $input
	 * @param array $init
	 */
	public function fetch($input, array $init = []):HttpPromise {
		$deferred = new Deferred();
		$deferredPromise = $deferred->promise();

		$uri = $this->ensureUriInterface($input);

		if($input instanceof RequestInterface) {
// TODO: Set init keys from RequestInterface here.
		}

		$this->requestResolver->add(
			$uri,
			$init,
			$deferred
		);

		$newPromise = new Promise($this->loop);

		$deferredPromise->then(function(ResponseInterface $response)
		use($newPromise) {
			$newPromise->resolve($response);
		});

		return $newPromise;
	}

	public function getOptions():array {
		return $this->options;
	}

	/**
	 * @param string|UriInterface $uri
	 * @return string
	 */
	public function ensureUriInterface($uri):UriInterface {
		if(is_string($uri)) {
			$uri = new Uri($uri);
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
		$this->wait();
		return new Promise($this->loop);
	}
}