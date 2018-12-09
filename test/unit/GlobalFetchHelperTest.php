<?php
namespace Gt\Fetch;

use PHPUnit\Framework\TestCase;

class GlobalFetchHelperTest extends TestCase {

	/**
	 * Asserts that the fetch method is called for each of the magic methods on the
	 * GlobalFetchHelper abstract class
	 */
	public function testHttpMethods() {
		$observerArray = [];

		foreach(GlobalFetchHelper::HTTP_METHODS as $httpMethod) {
			$lowerCaseMethod = strtolower($httpMethod);
			$uri = "fake://example.com/$lowerCaseMethod-test";

			$initArray = [
				"method" => $httpMethod,
			];

			$observerArray[$httpMethod] = $this->getMockBuilder(
				GlobalFetchHelper::class
			)
				->setMethods(["fetch"])
				->getMock();

			$observerArray[$httpMethod]->expects($this->once())
				->method("fetch")
				->with($uri, $initArray);

			$observerArray[$httpMethod]->$lowerCaseMethod($uri);
		}
	}
}