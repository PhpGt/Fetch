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
private $curlMulti;

private $requestArray = [];
private $deferredArray = [];
private $index;
private $runningStatus = null;

public function __construct(
string $curlMultiClass = "\PHPCurl\CurlWrapper\CurlMulti") {
	$this->curlMulti = new $curlMultiClass();
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
	if(is_null($this->runningStatus)) {
		$this->start();
	}

	while(false !== ($message = $this->curlMulti->infoRead($messageCount))) {
		var_dump($message);
	}

	// foreach($this->requestArray as $i => $request) {
		// $deferred = $this->deferredArray[$i];
	// }
}

private function start() {
// Add curl handles to the curlMulti stack.
	foreach($this->requestArray as $i => $request) {
		$successCode = $this->curlMulti->add($request->getCurlHandle());

		if($successCode !== 0) {
			throw new CurlMultiException($successCode);
		}
	}

	$this->runningStatus = null;

// Execute all curl handles on the curlMulti stack.
	while(CURLM_CALL_MULTI_PERFORM
	=== $this->curlMulti->exec($this->runningStatus));
}

}#