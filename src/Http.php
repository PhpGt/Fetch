<?php
namespace phpgt\fetch;

use React\EventLoop\Factory as EventLoopFactory;
use GuzzleHttp\Promise\Promise;

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
 * @var phpgt\Fetch\Request
 */
private $input;
private $promiseArray;

public function __construct(float $interval = 0.1) {
	$this->loop = EventLoopFactory::create();
}

public function tick() {
}

/**
 * @param string|Request $input Defines the resource that you wish to fetch
 * @param array $init An associative array containing any custom settings that
 * you want to apply to the request.
 *
 * @return GuzzleHttp\Promise\Promise
 */
public function request($input, array $init = []) {
	$promise = new Promise();

	if(!$input instanceof Request) {
		$input = new Request($input, $init);
	}

	$this->input = $input;

	$this->promiseArray []= $promise;
	return $promise;
}

public function wait() {
	$this->timer = $this->loop->addTimer(0.1, [$this, "tick"]);
	$this->loop->run($this->timer);
}

}#