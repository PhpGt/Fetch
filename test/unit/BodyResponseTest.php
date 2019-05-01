<?php
namespace Gt\Fetch\Test;

use Gt\Fetch\BodyResponse;
use Gt\Http\Header\ResponseHeaders;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use React\EventLoop\LoopInterface;

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
		$promise->then(function($fulfilledValue)use(&$sutOutput) {
			$sutOutput = $fulfilledValue;
		});
		$sut->endDeferredResponse();

		self::assertEquals($exampleContents, $sutOutput);
	}
}