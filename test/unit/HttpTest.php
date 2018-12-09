<?php
namespace Gt\Fetch;

use Gt\Curl\Curl;
use Gt\Curl\CurlInterface;
use Gt\Curl\CurlMulti;
use Gt\Curl\CurlMultiInterface;
use Gt\Fetch\Test\Helper\TestCurl;
use Gt\Fetch\Test\Helper\TestCurlMulti;
use Gt\Http\Request;
use Gt\Http\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpTest extends TestCase {
	public function testFetchBodyResponsePromise() {
		$fakeStatus = null;

		$http = new Http(
			[],
			0.01,
			TestCurl::class,
			TestCurlMulti::class
		);
		$http->fetch("test://should-return")
		->then(function(BodyResponse $response)use(&$fakeStatus) {
			$fakeStatus = $response->status;
		});

		$http->wait();
		self::assertEquals(999, $fakeStatus);
	}
}