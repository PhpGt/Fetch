<?php
namespace phpgt\fetch;

use React\EventLoop\Factory as EventLoopFactory;
use React\Promise\Deferred;
use React\Promise\Promise;

class Http {

/**
 * @var React\EventLoop\LoopInterface
 */
private $loop;
/**
 * @var React\EventLoop\Timer\TimerInterface
 */
private $timer;
/**
 * @var phpgt\Fetch\RequestResolver
 */
private $requestResolver;

public function __construct(float $interval = 0.1) {
	$this->loop = EventLoopFactory::create();
	$this->requestResolver = new RequestResolver();
}

/**
 * @param string|Request $input Defines the resource that you wish to fetch
 * @param array $init An associative array containing any custom settings that
 * you want to apply to the request.
 *
 * @return GuzzleHttp\Promise\Promise
 */
public function request($input, array $init = []) {
	$deferred = new Deferred();
	$promise = $deferred->promise();

	if(!$input instanceof Request) {
		$input = new Request($input, $init);
	}

	$this->requestResolver->add($input, $deferred);

	return $promise;
}

public function tick() {
	foreach($this->requestResolver as $input => $deferred) {
		$deferred->resolve($input);
	}

	$this->loop->cancelTimer($this->timer);
}

/**
 * Executes all promises in parallel, not returning until all requests have
 * completed.
 */
public function wait() {
	$this->timer = $this->loop->addTimer(0.1, [$this, "tick"]);
	$this->loop->run($this->timer);
}

/**
 * Executes all promises in parallel, returning a promise that resolves when
 * all HTTP requests have completed.
 *
 * @return GuzzleHttp\Promise\Promise Resolved when all HTTP requests have
 * completed
 */
public function all() {
	$deferred = new Deferred();
	$this->wait();
	$deferred->resolve(true);

	return $deferred->promise();
}

}#