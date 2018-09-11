<?php
namespace Gt\Fetch;

use Gt\Curl\CurlMulti;
use Psr\Http\Message\UriInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

class RequestResolver {
	protected $loop;
	protected $curlMulti;
	/** @var DeferredUri[] */
	protected $deferredUrlList;

	public function __construct(
		LoopInterface $loop,
		string $curlMultiClass = CurlMulti::class
	) {
		$this->loop = $loop;
		$this->curlMulti = new $curlMultiClass();
		$this->deferredUrlList = [];
	}

	public function add(UriInterface $uri, Deferred $deferred):void {
		$this->deferredUrlList []= new DeferredUri(
			$uri,
			$deferred
		);
	}

	public function tick():void {

	}
}