<?php
namespace Gt\Fetch;

use Gt\Curl\CurlMulti;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

class RequestResolver {
	private $loop;
	private $curlMulti;

	public function __construct(
		LoopInterface $loop,
		string $curlMultiClass = CurlMulti::class
	) {
		$this->loop = $loop;
		$this->curlMulti = new $curlMultiClass();
	}

	public function add(string $uri, Deferred $deferred) {

	}

	public function tick() {

	}
}