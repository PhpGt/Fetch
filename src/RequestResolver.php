<?php
namespace phpgt\fetch;

use React\Promise\Deferred;

/**
 * Contains multiple requests and their promises.
 */
class RequestResolver {

/**
 * @var PHPCurl\CurlWrapper\CurlMulti
 */
private $curlMultiHandle;

private $requestArray = [];
private $deferredArray = [];
private $index;

public function __construct(
string $curlMultiClass = "\PHPCurl\CurlWrapper\CurlMulti") {
	$this->curlMultiHandle = new $curlMultiClass();
}

public function add(Request $request, Deferred $deferred) {
	$this->requestArray []= $request;
	$this->deferredArray []= $deferred;
}

/**
 * Called from an event loop. This function periodically checks the status of
 * the requests within requestArray, resolving corresponding Deferred objects
 * as requests complete.
 */
public function tick() {
	for($i = 0, $length = count($this->requestArray); $i < $length; $i++) {
		$this->deferredArray[$i]->resolve(true);
	}
}

}#