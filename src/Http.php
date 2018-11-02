<?php
namespace Gt\Fetch;

use Gt\Http\Uri;
use Http\Client\HttpClient;
use Http\Client\HttpAsyncClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class Http extends GlobalFetchHelper implements HttpClient, HttpAsyncClient {
	const REFERRER = "PhpGt/Fetch";

	/** @var float */
	protected $interval;
	/** @var LoopInterface */
	protected $loop;
	/** @var RequestResolver */
	protected $requestResolver;
	/** @var array cURL options */
	protected $options = [
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_REFERER => self::REFERRER,
	];
	protected $timer;

	public function __construct(
		array $options = [],
		float $interval = 0.01
	) {
		$this->options = $options + $this->options;
		$this->interval = $interval;

		$this->loop = Factory::create();
		$this->requestResolver = new RequestResolver($this->loop);
	}

	/**
	 * @interface HttpClient
	 */
	public function sendRequest(
		RequestInterface $request
	):ResponseInterface {
		// TODO: Implement sendRequest() method.
	}

	/**
	 * @interface HttpAsyncClient
	 */
	public function sendAsyncRequest(
		RequestInterface $request
	):Promise {
		// TODO: Implement sendAsyncRequest() method.
	}

	/**
	 * Creates a new Deferred object to perform the resolution of the request and
	 * returns a PSR-7 compatible promise that represents the result of the response
	 *
	 * Long-hand for the GlobalFetchHelper get, head, post, etc.
	 *
	 * @param string|UriInterface $input
	 * @param array $init
	 * @return PromiseInterface
	 */
	public function fetch($input, array $init = []):PromiseInterface {
		$deferred = new Deferred();
		$promise = $deferred->promise();

		$uri = $this->ensureUriInterface($input);

		$this->requestResolver->add(
			$uri,
			$init,
			$deferred
		);

		return $promise;
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
		$timer = $this->loop->addPeriodicTimer(
			$this->interval,
			[$this->requestResolver, "tick"]
		);
		$this->loop->run();
	}

	/**
	 * Begins execution of all promises, returning its own Promise that will
	 * resolve when the last HTTP request is fully resolved.
	 */
	public function all():PromiseInterface {
		$deferred = new Deferred();
		$promise = $deferred->promise();
		$this->wait();
		$deferred->resolve(true);
		return $promise;
	}
}