<?php
namespace Gt\Fetch;

use Exception;
use Http\Promise\Promise as HttpPromise;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use RuntimeException;

class Promise implements HttpPromise {
	protected $loop;
	protected $state;

	/** @var callable */
	protected $onFulfilled;
	/** @var callable */
	protected $onRejected;
	/** @var ResponseInterface */
	protected $response;
	/** @var Exception */
	protected $exception;

	public function __construct(LoopInterface $loop) {
		$this->loop = $loop;
		$this->state = self::PENDING;
	}

	/**
	 * Adds behavior for when the promise is resolved or rejected (response will be available, or error happens).
	 *
	 * If you do not care about one of the cases, you can set the corresponding callable to null
	 * The callback will be called when the value arrived and never more than once.
	 *
	 * @param callable $onFulfilled called when a response will be available
	 * @param callable $onRejected called when an exception occurs
	 *
	 * @return HttpPromise a new resolved promise with value of the executed callback (onFulfilled / onRejected)
	 */
	public function then(callable $onFulfilled = null, callable $onRejected = null) {
		$newPromise = new self($this->loop);

		if(is_null($onFulfilled)) {
			$onFulfilled = function(ResponseInterface $response) {
				return $response;
			};
		}
		if(is_null($onRejected)) {
			$onRejected = function(Exception $exception) {
				return $exception;
			};
		}

		$this->onFulfilled = function(ResponseInterface $response)
		use($onFulfilled, $newPromise) {
			try {
				$return = $onFulfilled($response);
				$newPromise->resolve($return ?? $response);
			}
			catch(Exception $exception) {
				$newPromise->reject($exception);
			}
		};

		$this->onRejected = function(Exception $exception)
		use($onRejected, $newPromise) {
			try {
				$newPromise->resolve($onRejected($exception));
			}
			catch(Exception $exception) {
				$newPromise->reject($exception);
			}
		};

		if($this->getState() === self::FULFILLED) {
			$this->doResolve($this->response);
		}

		if($this->getState() === self::REJECTED) {
			$this->doReject($this->exception);
		}

		return $newPromise;
	}

	/**
	 * Returns the state of the promise, one of PENDING, FULFILLED or REJECTED.
	 *
	 * @return string
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * Wait for the promise to be fulfilled or rejected.
	 *
	 * When this method returns, the request has been resolved and if callables have been
	 * specified, the appropriate one has terminated.
	 *
	 * When $unwrap is true (the default), the response is returned, or the exception thrown
	 * on failure. Otherwise, nothing is returned or thrown.
	 *
	 * @param bool $unwrap Whether to return resolved value / throw reason or not
	 *
	 * @return mixed Resolved value, null if $unwrap is set to false
	 *
	 * @throws \Exception the rejection reason if $unwrap is set to true and the request failed
	 */
	public function wait($unwrap = true) {
		$loop = $this->loop;
		$state = $this->getState();

		while($state === self::PENDING) {
			$loop->futureTick(function() use($loop) {
				$loop->stop();
			});

			$loop->run();
		}

		if($unwrap) {
			if($state === self::REJECTED) {
				throw $this->exception;
			}

			return $this->response;
		}

		return null;
	}

	public function resolve(ResponseInterface $response):void {
		if($this->getState() !== self::PENDING) {
			throw new RuntimeException("Promise is already resolved");
		}

		$this->state = self::FULFILLED;
		$this->response = $response;
		$this->doResolve($response);
	}

	public function reject(Exception $exception) {
		if($this->getState() !== self::PENDING) {
			throw new RuntimeException("Promise is already resolved");
		}

		$this->state = self::REJECTED;
		$this->exception = $exception;
		$this->doReject($exception);
	}

	protected function doResolve(ResponseInterface $response):void {
		$onFulfilled = $this->onFulfilled;

		if(!is_null($this->onFulfilled)) {
			$onFulfilled($response);
		}
	}

	protected function doReject(Exception $exception):void {
		$onRejected = $this->onRejected;

		if(!is_null($this->onRejected)) {
			$onRejected($exception);
		}
	}
}