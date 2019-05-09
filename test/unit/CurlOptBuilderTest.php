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

	public function testAsCurlOptArrayEmpty() {
		$sut = new CurlOptBuilder(null, []);
		$curlOptArray = $sut->asCurlOptArray();
		self::assertEquals([], $curlOptArray);
	}

	public function testSetMethod() {
		$sut = new CurlOptBuilder(null, [
			"method" => "POST",
		]);
		self::assertEquals([
			CURLOPT_CUSTOMREQUEST => "POST",
		], $sut->asCurlOptArray());
	}

	public function testSetHeaders() {
		$headersArray = [
			"User-Agent" => "PHPUnit",
			"X-Project" => "phpgt/fetch",
		];

		$sut = new CurlOptBuilder(null, [
			"headers" => $headersArray
		]);

		$expectedCurlHeaders = [];
		foreach($headersArray as $key => $value) {
			$expectedCurlHeaders []= "$key: $value";
		}
		self::assertEquals(
			$expectedCurlHeaders,
			$sut->asCurlOptArray()[CURLOPT_HTTPHEADER]
		);
	}
}