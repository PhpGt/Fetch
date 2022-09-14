<?php
namespace Gt\Fetch;

use Exception;
use Gt\Async\Loop;
use Http\Promise\Promise as HttpPromise;
use RuntimeException;

class Promise implements HttpPromise {
	protected $loop;
	protected $state;

	/** @var callable */
	protected $onFulfilled;
	/** @var callable */
	protected $onRejected;
	protected $resolvedValue;
	/** @var Exception */
	protected $exception;

	public function __construct(Loop $loop) {
		$this->loop = $loop;
		$this->state = self::PENDING;
	}

	public function then(
		callable $onFulfilled = null,
		callable $onRejected = null
	):self {
		$newPromise = new self($this->loop);

		if(is_null($onFulfilled)) {
			$onFulfilled = function($resolvedValue) {
				return $resolvedValue;
			};
		}
		if(is_null($onRejected)) {
			$onRejected = function(Exception $exception) {
				return $exception;
			};
		}

		$this->onFulfilled = function($resolvedValue)
		use($onFulfilled, $newPromise) {
			try {
				$return = $onFulfilled($resolvedValue);

				if($return instanceof HttpPromise) {
					$return->then(function($innerResolvedValue) use($newPromise) {
						$newPromise->resolve($innerResolvedValue);
					});
				}
				else {
					$newPromise->resolve($return ?? $resolvedValue);
				}
			}
			catch(Exception $exception) {
				$newPromise->reject($exception);
			}
		};

		$this->onRejected = function(Exception $exception)
		use($onRejected, $newPromise) {
			$return = $onRejected($exception);
			$newPromise->reject($return);
		};

		if($this->getState() === self::FULFILLED) {
			$this->doResolve($this->resolvedValue);
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

			return $this->resolvedValue;
		}

		return null;
	}

	public function resolve($resolvedValue):void {
		if($this->getState() !== self::PENDING) {
			throw new RuntimeException("Promise is already resolved");
		}

		$this->state = self::FULFILLED;
		$this->resolvedValue = $resolvedValue;
		$this->doResolve($resolvedValue);
	}

	public function reject(Exception $exception) {
		if($this->getState() !== self::PENDING) {
			throw new RuntimeException("Promise is already resolved");
		}

		$this->state = self::REJECTED;
		$this->exception = $exception;
		$this->doReject($exception);
	}

	protected function doResolve($resolvedValue):void {
		$onFulfilled = $this->onFulfilled;

		if(!is_null($this->onFulfilled)) {
			$onFulfilled($resolvedValue);
		}
	}

	protected function doReject(Exception $exception):void {
		$onRejected = $this->onRejected;

		if(!is_null($this->onRejected)) {
			$onRejected($exception);
		}
	}
}
