<?php
namespace Gt\Fetch;

use Http\Client\HttpClient;
use Http\Client\HttpAsyncClient;
use Psr\Http\Message\UriInterface;
use React\EventLoop\StreamSelectLoop;
use React\Promise\Deferred;
use Psr\Http\Message\RequestInterface;
use React\EventLoop\Factory as EventLoopFactory;
use React\Promise\PromiseInterface;

class Http extends GlobalFetchHelper implements HttpClient, HttpAsyncClient {

	const REFERRER = "PhpGt/Fetch";

	/** @var float */
	protected $interval;
	/** @var StreamSelectLoop */
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

		$this->loop = EventLoopFactory::create();
		$this->requestResolver = new RequestResolver($this->loop);
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

		$uri = $this->ensureStringUri($input);

		$this->requestResolver->add($uri, $deferred);
		return $promise;
	}

	public function getOptions():array {
		return $this->options;
	}

	/**
	 * @param string|UriInterface $uri
	 * @return string
	 */
	public function ensureStringUri($uri):string {
		if($uri instanceof UriInterface) {
			$uri = (string)$uri;
		}

		return $uri;
	}

	/**
	 * Executes all promises in parallel, returning only when all promises
	 * have been fulfilled.
	 */
	public function wait() {
		$this->timer = $this->loop->addPeriodicTimer(
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

	public function sendAsyncRequest(RequestInterface $request) {
		// TODO: Implement sendAsyncRequest() method.
	}

	public function sendRequest(RequestInterface $request) {
		// TODO: Implement sendRequest() method.
	}
}