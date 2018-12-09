<?php
namespace Gt\Fetch;

use Gt\Curl\Curl;
use Gt\Curl\CurlInterface;
use Gt\Curl\CurlMulti;
use Gt\Curl\CurlMultiInterface;
use Gt\Fetch\Test\Helper\ResponseSimulator;
use Gt\Fetch\Test\Helper\TestCurl;
use Gt\Fetch\Test\Helper\TestCurlMulti;
use Gt\Http\Request;
use Gt\Http\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpTest extends TestCase {
	public function testFetchBodyResponsePromise() {
		$http = new Http(
			[],
			0.01,
			TestCurl::class,
			TestCurlMulti::class
		);

		$fakeStatus = null;

		$http->fetch("test://should-return")
		->then(function(BodyResponse $response)use(&$fakeStatus) {
			$fakeStatus = $response->status;
		});

		$http->wait();
		self::assertEquals(999, $fakeStatus);
	}

	public function testSendRequest() {
		$htmlHelloFetch = "<!doctype html><h1>Hello, Fetch!</h1>";
		ResponseSimulator::setExpectedBody($htmlHelloFetch);

		$http = new Http(
			[],
			0.01,
			TestCurl::class,
			TestCurlMulti::class
		);

		$uri = self::createMock(Uri::class);
		$request = self::createMock(Request::class);
		$request->method("getUri")
			->willReturn($uri);

		/** @var RequestInterface $request */

		$response = $http->sendRequest($request);
		self::assertInstanceOf(
			ResponseInterface::class,
			$response
		);
		$body = $response->getBody();
		self::assertEquals(
			$htmlHelloFetch,
			$body->getContents()
		);
	}


}