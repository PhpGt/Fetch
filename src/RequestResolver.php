<?php
namespace Gt\Fetch;

use Gt\Curl\Curl;
use Gt\Curl\CurlMulti;
use Psr\Http\Message\UriInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

class RequestResolver {
	protected $loop;
	/** @var CurlMulti */
	protected $curlMulti;

	public function __construct(
		LoopInterface $loop,
		string $curlMultiClass = CurlMulti::class
	) {
		$this->loop = $loop;
		$this->curlMulti = new CurlMulti();

$this->handle = curl_multi_init();

	}

	public function add(UriInterface $uri, Deferred $deferred):void {
		$this->curlMulti->add(new Curl($uri));

curl_multi_add_handle($this->handle, curl_init($uri));
	}

	public function tick():void {

	}

	public function temporaryThing() {


		do {
			$mrc = curl_multi_exec($this->handle, $active);

			if ($state = curl_multi_info_read($this->handle)) {
				print_r($state);
				$info = curl_getinfo($state['handle']);
				print_r($info);
//				$callback(curl_multi_getcontent($state['handle']), $info);
				curl_multi_remove_handle($this->handle, $state['handle']);
			}
			usleep(10000); // stop wasting CPU cycles and rest for a couple ms

		} while ($mrc == CURLM_CALL_MULTI_PERFORM || $active);
	}
}