<?php
namespace Gt\Fetch;

use Http\Client\HttpClient;
use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Factory as EventLoopFactory;

class Http extends GlobalFetchHelper implements HttpClient, HttpAsyncClient {

const REFERRER = "PhpGt/Fetch";

private $interval;
private $loop;
private $requestResolver;
private $options = [
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_REFERER => self::REFERRER,
];

public function __construct(array $options = [], float $interval = 0.01) {
	$this->options = $options + $this->options;
	$this->interval = $interval;

	$this->loop = EventLoopFactory::create();
	$this->requestResolver = new RequestResolver($this->loop);
}

public function getOptions():array {
	return $this->options;
}

public function fetch($input, array $init = []):Promise {
	// TODO: Implement fetch() method.
}

/**
 * Sends a PSR-7 request.
 *
 * @param RequestInterface $request
 *
 * @return ResponseInterface
 *
 * @throws \Http\Client\Exception If an error happens during processing the request.
 * @throws \Exception             If processing the request is impossible (eg. bad configuration).
 */
public function sendRequest(RequestInterface $request) {
	// TODO: Implement sendRequest() method.
}

/**
 * Sends a PSR-7 request in an asynchronous way.
 *
 * Exceptions related to processing the request are available from the returned Promise.
 *
 * @param RequestInterface $request
 *
 * @return Promise Resolves a PSR-7 Response or fails with an Http\Client\Exception.
 *
 * @throws \Exception If processing the request is impossible (eg. bad configuration).
 */
public function sendAsyncRequest(RequestInterface $request) {
	// TODO: Implement sendAsyncRequest() method.
}

}#