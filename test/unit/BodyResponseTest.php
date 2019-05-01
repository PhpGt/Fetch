<?php
namespace Gt\Fetch\Test;

use Gt\Curl\JsonDecodeException;
use Gt\Fetch\BodyResponse;
use Gt\Fetch\Promise;
use Gt\Http\Header\ResponseHeaders;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use React\EventLoop\LoopInterface;
use SplFixedArray;
use stdClass;

class BodyResponseTest extends TestCase {
	public function testText() {
		$exampleContents = "Example stream contents";

		/** @var MockObject|LoopInterface $loop */
		$loop = self::createMock(LoopInterface::class);
		/** @var MockObject|StreamInterface $stream */
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn($exampleContents);

		$sutOutput = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);
		$sut->startDeferredResponse($loop);
		$promise = $sut->text();
		$promise->then(function(string $fulfilledValue)use(&$sutOutput) {
			$sutOutput = $fulfilledValue;
		});
		$sut->endDeferredResponse();

		self::assertEquals($exampleContents, $sutOutput);
	}

	public function testBlob() {
		$exampleContents = "Example stream contents";

		/** @var MockObject|LoopInterface $loop */
		$loop = self::createMock(LoopInterface::class);
		/** @var MockObject|StreamInterface $stream */
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn($exampleContents);

		$sutOutput = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);
		$sut->startDeferredResponse($loop);
		$promise = $sut->blob();
		$promise->then(function(string $fulfilledValue)use(&$sutOutput) {
			$sutOutput = $fulfilledValue;
		});
		$sut->endDeferredResponse();

		self::assertEquals($exampleContents, $sutOutput);
	}

	public function testJson() {
		$exampleObj = new StdClass();
		$exampleObj->test = "Example";
		$jsonString = json_encode($exampleObj);

		/** @var MockObject|LoopInterface $loop */
		$loop = self::createMock(LoopInterface::class);
		/** @var MockObject|StreamInterface $stream */
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn($jsonString);

		$sutOutput = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);
		$sut->startDeferredResponse($loop);
		$promise = $sut->json();
		$promise->then(function(StdClass $fulfilledValue)use(&$sutOutput) {
			$sutOutput = $fulfilledValue;
		});
		$sut->endDeferredResponse();

		self::assertEquals($exampleObj, $sutOutput);
	}

	public function testInvalidJson() {
		$jsonString = "{'this;': is not valid JSON'}";

		/** @var MockObject|LoopInterface $loop */
		$loop = self::createMock(LoopInterface::class);
		/** @var MockObject|StreamInterface $stream */
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn($jsonString);

		$sutOutput = null;
		$sutError = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);
		$sut->startDeferredResponse($loop);
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

		/** @var MockObject|LoopInterface $loop */
		$loop = self::createMock(LoopInterface::class);
		/** @var MockObject|StreamInterface $stream */
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn(pack("C*", ...$bytes));

		$sutOutput = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);
		$sut->startDeferredResponse($loop);
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

		/** @var MockObject|LoopInterface $loop */
		$loop = self::createMock(LoopInterface::class);
		/** @var MockObject|StreamInterface $stream */
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn(http_build_query($exampleKVP));

		$sutOutput = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);
		$sut->startDeferredResponse($loop);
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

		/** @var MockObject|LoopInterface $loop */
		$loop = self::createMock(LoopInterface::class);
		/** @var MockObject|StreamInterface $stream */
		$stream = self::createMock(StreamInterface::class);
		$stream->method("tell")
			->willReturn(0);
		$stream->method("getContents")
			->willReturn($exampleContents);

		$sutOutput = null;
		$sut = new BodyResponse();
		$sut = $sut->withBody($stream);

		self::assertNull(
			$sut->deferredResponseStatus()
		);
		$sut->startDeferredResponse($loop);
		self::assertEquals(
			Promise::PENDING,
			$sut->deferredResponseStatus()
		);

		$sut->endDeferredResponse();
		self::assertEquals(
			Promise::FULFILLED,
			$sut->deferredResponseStatus()
		);
	}
}