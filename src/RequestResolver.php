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
private $responseArray = [];
private $index;
private $openConnectionCount = null;

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
	if(is_null($this->openConnectionCount)) {
		$this->start();
	}

	do {
		$info = $this->curlMulti->infoRead($messagesInQueue);

		if($info === false) {
			break;
		}

		$request = $this->matchRequest($info["handle"]);
		if($request->getResponseCode() === 200) {
			$requestIndex = array_search($request, $this->requestArray);
			$curl = $request->getCurlHandle();
			// $this->deferredArray[$requestIndex]->resolve(
			// 	new Response(
			// 		$this->curlMulti->getContent($curl),
			// 		$curl->getInfo()
			// 	)
			// );
		}

	}while($messagesInQueue > 0);

	if($this->openConnectionCount === 0) {
// $this->stopLoopSomehow() no need for comment - function should explain.
		die("ALL DONE!");
	}

// Wait for activity on any of the handles.
	$this->curlMulti->select();

// Execute the multi handle for processing next tick.
	$status = $this->curlMulti->exec($this->openConnectionCount);
	if($status !== CURLM_OK) {
		throw new CurlMultiException($status);
	}
}

/**
 * Adds each request's curl handle to the multi stack.
 */
private function start() {
	foreach($this->requestArray as $i => $request) {
		$response = new Response($this->deferredArray[$i]);
		$this->responseArray []= $response;
		$curl = $request->setStream([$response, "stream"]);

		if(0 !== $this->curlMulti->add($request->getCurlHandle())) {
			throw new CurlMultiException($successCode);
		}
	}
}

/**
 * Matches and returns the Request object containing the provided curl handle.
 *
 * @return Request
 */
private function matchRequest($ch) {
	foreach($this->requestArray as $request) {
		if($request->getCurlHandle()->getHandle() === $ch) {
			return $request;
		}
	}

	throw new CurlHandleMissingException($ch);
}

}#