<?php
namespace phpgt\fetch;

require_once __DIR__ . "/../../vendor/autoload.php";

class ExampleTest extends \PHPUnit_Framework_TestCase {

const URL = "https://github.com/phpgt/fetch";

public function testPromiseReturned() {
	$http = new Http();
	$promise = $http->request(self::URL);
	$this->assertInstanceOf("\React\Promise\Promise", $promise);

	$promise2 = $http->all();
	$this->assertInstanceOf("\React\Promise\Promise", $promise2);
}

public function testCallbackInvoked() {
	$stubStdClass = $this->getMockBuilder(stdClass::class)
		->setMethods(["mockCallback"])
		->getMock();
	$stubStdClass->expects($this->once())
		->method("mockCallback");

	$http = new Http();
	$http->request(self::URL)
		->then([$stubStdClass, "mockCallback"]);

	$http->wait();
}

public function testMultipleCallbacksInvoked() {
	$stubStdClass = $this->getMockBuilder(stdClass::class)
		->setMethods(["mockCallback1", "mockCallback2", "mockCallback3"])
		->getMock();
	$stubStdClass->expects($this->once())
		->method("mockCallback1");
	$stubStdClass->expects($this->once())
		->method("mockCallback2");
	$stubStdClass->expects($this->once())
		->method("mockCallback3");

	$http = new Http();
	$http->request(self::URL)
		->then([$stubStdClass, "mockCallback1"])
		->then([$stubStdClass, "mockCallback2"]);

	$http->request(self::URL)
		->then([$stubStdClass, "mockCallback3"]);

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