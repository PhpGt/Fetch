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
use Http\Promise\Promise as HttpPromise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpTest extends TestCase {
	/** @runInSeparateProcess */
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

	public function testFetchBodyResponsePromiseResolves() {
		$expectedHtml = "<!doctype html><h1>Hello, Fetch!</h1>";
		ResponseSimulator::setExpectedBody($expectedHtml);
		$http = new Http(
			[],
			0.01,
			TestCurl::class,
			TestCurlMulti::class
		);

		$actualResponse = null;

		$http->fetch("test://should-return")
		->then(function(BodyResponse $response)use(&$fakeStatus) {
			return $response->text();
		})
		->then(function(string $text)use(&$actualResponse) {
			$actualResponse = $text;
		});

//		$finalPromiseResolved = false;

		$http->wait();

//		self::assertTrue($finalPromiseResolved);
		self::assertEquals($expectedHtml, $actualResponse);

	}

	/** @runInSeparateProcess */
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

	/** @runInSeparateProcess */
	public function testAsyncRequest() {
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

		$promise = $http->sendAsyncRequest($request);
		self::assertInstanceOf(
			Promise::class,
			$promise
		);

		$fakeStatus = null;

		$promise->then(function(BodyResponse $response) use(&$fakeStatus) {
			$fakeStatus = $response->status;
		});

		$http->wait();

		self::assertEquals(
			999,
			$fakeStatus
		);
	}

	public function testGetOptions() {
		$options = [
			uniqid() => uniqid(),
			uniqid() => uniqid(),
			uniqid() => uniqid(),
			uniqid() => uniqid(),
			uniqid() => uniqid(),
		];
		$http = new Http($options);
		$actualOptions = $http->getoptions();

		foreach($options as $key => $value) {
			self::assertEquals($value, $actualOptions[$key]);
		}

		self::assertGreaterThan(count($options), count($actualOptions));
	}
}