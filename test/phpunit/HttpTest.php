<?php
namespace Gt\Fetch\Test;

use Gt\Fetch\Http;
use Gt\Fetch\Response\FetchResponse;
use Gt\Fetch\Test\Helper\ResponseSimulator;
use Gt\Fetch\Test\Helper\TestCurl;
use Gt\Fetch\Test\Helper\TestCurlMulti;
use Gt\Http\Request;
use Gt\Http\Uri;
use Gt\Promise\Promise;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

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
		->then(function(FetchResponse $response)use(&$fakeStatus) {
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
		->then(function(FetchResponse $response)use(&$fakeStatus) {
			return $response->text();
		})
		->then(function(string $text)use(&$actualResponse) {
			$actualResponse = $text;
		});

		$http->wait();
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

		$promise = $http->sendAsyncRequest($request);
		self::assertInstanceOf(
			Promise::class,
			$promise
		);

		$fakeStatus = null;

		$promise->then(function(FetchResponse $response) use(&$fakeStatus) {
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
		$actualOptions = $http->curlOptions;

		foreach($options as $key => $value) {
			self::assertEquals($value, $actualOptions[$key]);
		}

		self::assertGreaterThan(count($options), count($actualOptions));
	}

	public function testAll() {
		$http = new Http(
			[],
			0.01,
			TestCurl::class,
			TestCurlMulti::class
		);

		$actualResponse = null;

		$http->fetch("test://should-return")
			->then(function(FetchResponse $response)use(&$fakeStatus) {
				return $response->text();
			})
			->then(function(string $text)use(&$actualResponse) {
				$actualResponse = $text;
			});

		$finalPromiseResolved = false;

		$http->all()
		->then(function() use(&$finalPromiseResolved) {
			$finalPromiseResolved = true;
		});

		self::assertTrue($finalPromiseResolved);
	}

	/** @runInSeparateProcess */
	public function testPsrSendRequest() {
		$http = new Http(
			[],
			0.01,
			TestCurl::class,
			TestCurlMulti::class
		);
		/** @var MockObject|Uri $uri */
		$uri = self::createMock(Uri::class);
		$uri->method("__toString")
			->willReturn("test://test.from.phpunit");

		/** @var MockObject|Request $request */
		$request = self::createMock(Request::class);
		$request->method("getUri")
			->willReturn($uri);
		$response = $http->sendRequest($request);

		self::assertEquals(999, $response->getStatusCode());
	}

	/** @runInSeparateProcess */
	public function testPsrSendAsyncRequest() {
		$http = new Http(
			[],
			0.01,
			TestCurl::class,
			TestCurlMulti::class
		);
		/** @var MockObject|Uri $uri */
		$uri = self::createMock(Uri::class);
		$uri->method("__toString")
			->willReturn("test://test.from.phpunit");

		/** @var MockObject|Request $request */
		$request = self::createMock(Request::class);
		$request->method("getUri")
			->willReturn($uri);

		$responseCode = null;

		$http->sendAsyncRequest($request)
		->then(function(ResponseInterface $response)use(&$responseCode) {
			$responseCode = $response->getStatusCode();
		});

		self::assertNull($responseCode);

		$http->all();

		self::assertEquals(999, $responseCode);
	}

	public function testEnsureUriInterface() {
		$sut = new Http();
		$uri = $sut->ensureUriInterface("test://example");
		self::assertInstanceOf(UriInterface::class, $uri);

		$uriInterface = self::createMock(Uri::class);
		$uriInterface->method("__toString")
			->willReturn("test://example");
		$request = self::createMock(Request::class);
		$request->method("getUri")
			->willReturn($uriInterface);
		$uri = $sut->ensureUriInterface($request);
		self::assertInstanceOf(UriInterface::class, $uri);
	}
}
