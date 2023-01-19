<?php
namespace Gt\Fetch\Test\Response;

use Gt\Async\Loop;
use Gt\Curl\Curl;
use Gt\Fetch\Response\Blob;
use Gt\Fetch\Response\BodyResponse;
use Gt\Json\JsonDecodeException;
use Gt\Json\JsonKvpObject;
use Gt\Promise\Promise;
use Http\Promise\Promise as HttpPromiseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use SplFixedArray;
use StdClass;

class BodyResponseTest extends TestCase {
	public function testText() {
		$exampleContents = "Example stream contents";

		$loop = self::createMock(Loop::class);
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn($exampleContents);
		/** @var MockObject|Curl $curl */
		$curl = self::createMock(Curl::class);

		$sutOutput = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);
		$sut->startDeferredResponse($loop, $curl);
		$promise = $sut->text();
		$promise->then(function(string $fulfilledValue)use(&$sutOutput) {
			$sutOutput = $fulfilledValue;
		});
		$sut->endDeferredResponse();

		self::assertEquals($exampleContents, $sutOutput);
	}

	public function testBlob() {
		$exampleContents = "Example stream contents";

		$loop = self::createMock(Loop::class);
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn($exampleContents);
		$curl = self::createMock(Curl::class);

		$sutOutput = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);
		$sut->startDeferredResponse($loop, $curl);
		$promise = $sut->blob();
		$promise->then(function(Blob $fulfilledValue)use(&$sutOutput) {
			$sutOutput = $fulfilledValue;
		});
		$sut->endDeferredResponse();

		self::assertEquals($exampleContents, $sutOutput);
	}

	public function testJson() {
		$exampleObj = new StdClass();
		$exampleObj->test = "Example";
		$jsonString = json_encode($exampleObj);

		$loop = self::createMock(Loop::class);
		$stream = self::createMock(StreamInterface::class);
		/** @var MockObject|StreamInterface $stream */
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn($jsonString);
		/** @var MockObject|Curl $curl */
		$curl = self::createMock(Curl::class);

		/** @var null|JsonKvpObject $sutOutput */
		$sutOutput = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);
		$sut->startDeferredResponse($loop, $curl);
		$promise = $sut->json();
		$promise->then(function(JsonKvpObject $fulfilledValue)use(&$sutOutput) {
			$sutOutput = $fulfilledValue;
		});
		$sut->endDeferredResponse();

		foreach($exampleObj as $key => $value) {
			self::assertEquals($value, $sutOutput->getString($key));
		}
	}

	public function testInvalidJson() {
		$jsonString = "{'this;': is not valid JSON'}";

		$loop = self::createMock(Loop::class);
		$stream = self::createMock(StreamInterface::class);
		/** @var MockObject|StreamInterface $stream */
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn($jsonString);
		/** @var MockObject|Curl $curl */
		$curl = self::createMock(Curl::class);

		$sutOutput = null;
		$sutError = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);
		$sut->startDeferredResponse($loop, $curl);
		$promise = $sut->json();
		$promise->then(function($fulfilledValue)use(&$sutOutput) {
			$sutOutput = $fulfilledValue;
		}, function($errorValue)use(&$sutError) {
			$sutError = $errorValue;
		});
		$sut->endDeferredResponse();

		self::assertNull($sutOutput);
		self::assertInstanceOf(JsonDecodeException::class, $sutError);
	}

	public function testArrayBuffer() {
		$bytes = [
			84,  104, 101, 32, 113,  117, 105, 99,  107,
			32,  102, 111, 120, 32, 106, 117, 109, 112, 101,
			100, 32,  111, 118, 101, 114, 32,  116, 104, 101,
			32,  108, 97,  122, 121, 32,  98,  114, 111, 119,
			110, 32,  100, 111, 103,
		];

		$loop = self::createMock(Loop::class);
		$stream = self::createMock(StreamInterface::class);
		/** @var MockObject|StreamInterface $stream */
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn(pack("C*", ...$bytes));
		/** @var MockObject|Curl $curl */
		$curl = self::createMock(Curl::class);

		$sutOutput = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);
		$sut->startDeferredResponse($loop, $curl);
		$promise = $sut->arrayBuffer();
		$promise->then(function(SplFixedArray $fulfilledValue)use(&$sutOutput) {
			$sutOutput = $fulfilledValue;
		});
		$sut->endDeferredResponse();

		self::assertInstanceOf(SplFixedArray::class, $sutOutput);
		/** @var SplFixedArray $sutOutput */
		self::assertCount(count($bytes), $sutOutput);

		$byteAtPosition0 = $bytes[0];
		$byteAtPosition9 = $bytes[9];

		self::assertEquals(
			$byteAtPosition0,
			$sutOutput->offsetGet(0)
		);
		self::assertEquals(
			$byteAtPosition9,
			$sutOutput->offsetGet(9)
		);
	}

	public function testFormData() {
		$exampleKVP = [
			"organisation" => "phpgt",
			"repository" => "fetch",
		];

		$loop = self::createMock(Loop::class);
		$stream = self::createMock(StreamInterface::class);
		/** @var MockObject|StreamInterface $stream */
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn(http_build_query($exampleKVP));
		/** @var MockObject|Curl $curl */
		$curl = self::createMock(Curl::class);

		$sutOutput = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);
		$sut->startDeferredResponse($loop, $curl);
		$promise = $sut->formData();
		$promise->then(function(array $fulfilledValue)use(&$sutOutput) {
			$sutOutput = $fulfilledValue;
		});
		$sut->endDeferredResponse();

		self::assertIsArray($sutOutput);
		self::assertEquals("phpgt", $sutOutput["organisation"]);
		self::assertEquals("fetch", $sutOutput["repository"]);
	}

	public function testDeferredResponseStatus() {
		$exampleContents = "Example stream contents";

		$loop = self::createMock(Loop::class);
		$stream = self::createMock(StreamInterface::class);
		/** @var MockObject|StreamInterface $stream */
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn($exampleContents);
		/** @var MockObject|Curl $curl */
		$curl = self::createMock(Curl::class);

		$sutOutput = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);

		self::assertNull(
			$sut->deferredResponseStatus()
		);
		$sut->startDeferredResponse($loop, $curl);
		self::assertEquals(
			HttpPromiseInterface::PENDING,
			$sut->deferredResponseStatus()
		);

		$sut->endDeferredResponse();
		self::assertEquals(
			HttpPromiseInterface::FULFILLED,
			$sut->deferredResponseStatus()
		);
	}

	public function testGetHeaders() {
		$sut = new BodyResponse();
		$sut = $sut->withAddedHeader("X-Test-One", "Example1");
		$sut = $sut->withAddedHeader("X-Test-Two", "Example2");

		$headers = $sut->headers;
		self::assertEquals("Example1", $headers->get("X-Test-One"));
		self::assertEquals("Example2", $headers->get("X-Test-Two"));
	}

	public function testGetOk() {
		$sut = new BodyResponse();
		$sut = $sut->withStatus(200);
		self::assertTrue($sut->ok);

		$sut = new BodyResponse();
		$sut = $sut->withStatus(404);
		self::assertFalse($sut->ok);
	}

	public function testGetRedirected() {
		$loop = self::createMock(Loop::class);
		$curl = self::createMock(Curl::class);
		$curl->method("getInfo")
			->willReturn(0, 3);

		$sut = new BodyResponse();
		$sut->startDeferredResponse($loop, $curl);
		self::assertFalse($sut->redirected);
		self::assertTrue($sut->redirected);
	}

	public function testGetStatusText() {
		$sut = new BodyResponse(200);
		self::assertEquals("OK", $sut->statusText);

		$sut = new BodyResponse(404);
		self::assertEquals("Not Found", $sut->statusText);

		$sut = new BodyResponse(303);
		self::assertEquals("See Other", $sut->statusText);
	}

	public function testGetUrl() {
		$loop = self::createMock(Loop::class);
		$curl = self::createMock(Curl::class);
		$curl->method("getInfo")
			->willReturn("/", "/test", "/test/123");
		$sut = new BodyResponse();
		$sut->startDeferredResponse($loop, $curl);
		self::assertEquals("/", $sut->url);
		self::assertEquals("/test", $sut->url);
		self::assertEquals("/test/123", $sut->url);
	}

	public function testUndefinedProperty() {
		$sut = new BodyResponse();
		self::expectException(RuntimeException::class);
		self::expectExceptionMessage("Undefined property: test123");

		$sut->test123;
	}
}
