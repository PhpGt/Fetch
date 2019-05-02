<?php
namespace Gt\Fetch\Test;

use Gt\Fetch\CurlOptBuilder;
use Gt\Fetch\UnknownCurlOptException;
use PHPUnit\Framework\TestCase;

class CurlOptBuilderTest extends TestCase {
	public function testInvalidKey() {
		self::expectException(UnknownCurlOptException::class);
		$sut = new CurlOptBuilder(null, [
			"unknown" => "nothing",
		]);
	}
}