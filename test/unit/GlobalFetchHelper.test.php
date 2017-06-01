<?php
namespace Gt\Fetch;

class GlobalFetchHelperTest extends \PHPUnit_Framework_TestCase {

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

/**
 * Calling a magic method that doesn't match an allowed HTTP method should
 * behave exactly like calling a non-existent method should, by emitting an
 * error.
 *
 * @expectedException PHPUnit_Framework_Error
 */
public function testNonHttpMethod() {
	$httpMethod = "UNKNOWN";
	$lowerCaseMethod = strtolower($httpMethod);
	$uri = "fake://example.com/$lowerCaseMethod-test";

	$observer = $this->getMockBuilder(
		GlobalFetchHelper::class
	)
	->setMethods(["fetch"])
	->getMock();

	$observer->$lowerCaseMethod($uri);
}

}#