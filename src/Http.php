<?php
namespace phpgt\fetch;

use React\EventLoop\Factory as EventLoopFactory;
use React\Promise\Deferred;
use React\Promise\Promise;

/**
 * @method React\Promise\Promise get(string|Request $input, array $init)
 * @method React\Promise\Promise post(string|Request $input, array $init)
 * @method React\Promise\Promise head(string|Request $input, array $init)
 * @method React\Promise\Promise put(string|Request $input, array $init)
 * @method React\Promise\Promise delete(string|Request $input, array $init)
 * @method React\Promise\Promise options(string|Request $input, array $init)
 * @method React\Promise\Promise patch(string|Request $input, array $init)
 */
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

public function __call($name, $arguments) {
	switch($name) {
	case Request::METHOD_GET:
	case Request::METHOD_POST:
	case Request::METHOD_HEAD:
	case Request::METHOD_PUT:
	case Request::METHOD_DELETE:
	case Request::METHOD_OPTIONS:
	case Request::METHOD_PATCH:
		if(!isset($arguments[1])) {
			$arguments[1] = [];
		}
		$arguments[1]["method"] = $name;

		return call_user_func_array([$this, "request"], $arguments);
		break;

	default:
		trigger_error("Call to undefined method "
			. __CLASS__
			. "::"
			. $name
			. "()"
			, E_USER_ERROR
		);
	}
}

/**
 * @param string|Request $input Defines the resource that you wish to fetch
 * @param array $init An associative array containing any custom settings that
 * you wish to apply to the request
 *
 * @return React\Promise\Promise
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

/**
 * Executes all promises in parallel, not returning until all requests have
 * completed.
 */
public function wait() {
	$this->timer = $this->loop->addTimer(0.1, [$this->requestResolver, "tick"]);
	$this->loop->run($this->timer);
}

/**
 * Executes all promises in parallel, returning a promise that resolves when
 * all HTTP requests have completed.
 *
 * @return React\Promise\Promise Resolved when all HTTP requests have
 * completed
 */
public function all() {
	$deferred = new Deferred();
	$this->wait();
	$deferred->resolve(true);

	return $deferred->promise();
}

}#