<?php
namespace Gt\Fetch;

use Http\Promise\Promise as HttpPromise;
use React\Promise\Promise as DeferredPromise;

/**
 * A PSR-7 compatible promise that represents a Deferred's promise.
 */
class Promise implements HttpPromise {

/** @var DeferredPromise */
private $deferredPromise;

public function __construct(DeferredPromise $deferredPromise) {
	$this->deferredPromise = $deferredPromise;
}

/**
 * Adds behavior for when the promise is resolved or rejected (response will be available, or error happens).
 *
 * If you do not care about one of the cases, you can set the corresponding callable to null
 * The callback will be called when the value arrived and never more than once.
 *
 * @param callable $onFulfilled Called when a response will be available.
 * @param callable $onRejected Called when an exception occurs.
 *
 * @return HttpPromise A new resolved promise with value of the executed callback (onFulfilled / onRejected).
 */
public function then(
	callable $onFulfilled = null,
	callable $onRejected = null
) {
	// TODO: Implement then() method.
}

/**
 * Returns the state of the promise, one of PENDING, FULFILLED or REJECTED.
 *
 * @return string
 */
public function getState() {
	// TODO: Implement getState() method.
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
 * @throws \Exception The rejection reason if $unwrap is set to true and the request failed.
 */
public function wait($unwrap = true) {
	// TODO: Implement wait() method.
}

}#