<?php
namespace Gt\Fetch;

use React\Promise\Deferred;
use React\EventLoop\LoopInterface;

/**
 * Contains multiple requests and their promises.
 */
class RequestResolver {

/** @var \React\EventLoop\LoopInterface */
private $loop;
/** @var \PHPCurl\CurlWrapper\CurlMulti */
private $curlMulti;

// TODO: Should these be refactored out into objects?
/** @var Deferred[] */
private $deferredArray = [];
/** @var Response[] */
private $responseArray = [];
/** @var Request[] */
private $requestArray = [];
/** @var int  */
private $openConnectionCount = null;

public function __construct(LoopInterface $loop,
string $curlMultiClass = "\\PHPCurl\\CurlWrapper\\CurlMulti") {
	$this->loop = $loop;
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

// Set by the curlMulti->infoRead (passed by reference).  Note this is not
// the same as the number of requests or responses that are open.
	$messagesInQueue = 0;

	do {
// always returns false until at least one curl handle has response headers
// ready to read.  (The body might not be there yet though.)
		$info = $this->curlMulti->infoRead($messagesInQueue);

		if($info === false) {
			break;
		}

		$request = $this->matchRequest($info["handle"]);
        $requestIndex = array_search($request, $this->requestArray);
        $httpStatusCode = $request->getResponseCode();
        $this->responseArray[$requestIndex]->complete($httpStatusCode);

	} while($messagesInQueue > 0);

	if($this->openConnectionCount === 0) {
// TODO: Do we need to do anything else here?
		$this->loop->stop();
	}

// Wait for activity on any of the handles.
	$this->curlMulti->select();

// Execute the multi handle for processing next tick.
// openConnectionCount is passed by reference and updated by this call in each tick
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
		$response = new Response($this->deferredArray[$i], $this->loop);
		$this->responseArray []= $response;
		$request->setStream([$response, "stream"]);

        $successCode = $this->curlMulti->add($request->getCurlHandle());
		if($successCode !== 0) {
			throw new CurlMultiException($successCode);
		}
	}
}

/**
 * Matches and returns the Request object containing the provided curl handle.
 *
 * @param   $ch  mixed   Underlying lib-curl resource
 *
 * @return Request
 * @throws CurlHandleMissingException
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
