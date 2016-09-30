<?php
namespace phpgt\fetch;

require_once __DIR__ . "/../../vendor/autoload.php";

class ExampleTest extends \PHPUnit_Framework_TestCase {

const URL = "https://github.com/phpgt/fetch";

public function testPromiseReturned() {
	$http = new Http();
	$promise = $http->request(self::URL);
	$this->assertInstanceOf("\GuzzleHttp\Promise\Promise", $promise);

	$promise2 = $http->all();
	$this->assertInstanceOf("\GuzzleHttp\Promise\Promise", $promise2);
}

/**
 * @group failing
 */
public function testCallbackInvoked() {
	$mockPromise = $this->getMock("\GuzzleHttp\Promise\Promise");

	$mockCallback = $this->getMock("stdClass", ["mockCallback"]);
	$mockCallback->expects($this->once())
		->method("mockCallback")
		->will($this->returnValue($mockPromise));

	$http = new Http();
	$http->request(self::URL)
		->then([$mockCallback, "mockCallback"]);

	$http->wait();
}

public function testResponseType() {
	$http = new Http();
	$http->request(self::URL)
		->then(function($response) {
			$this->assertInstanceOf("\phpgt\fetch\Response", $response);
		});
	$http->wait();
}


}#