<?php
namespace Gt\Fetch;

use PHPCurl\CurlWrapper\CurlMulti;
use React\EventLoop\LoopInterface;

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

}