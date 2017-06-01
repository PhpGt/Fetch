<?php
namespace Gt\Fetch;

class HttpTest extends \PHPUnit_Framework_TestCase {

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
			$this->assertInstanceOf("\Gt\Fetch\Response", $response);
		});
	$http->wait();
}

public function testAllMethod() {
	$stubStdClass = $this->getMockBuilder(stdClass::class)
		->setMethods(["mockCallback"])
		->getMock();
	$stubStdClass->expects($this->once())
		->method("mockCallback");

	$http = new Http();
	$http->request(self::URL);

	$http->all()->then([$stubStdClass, "mockCallback"]);
}

public function testShorthandMethods() {
	$http = new Http();

	foreach(["get","post","head","put","delete","options","patch"] as $method) {
		$http->$method(self::URL);
	}

	$this->setExpectedException("PHPUnit_Framework_Error");
	$http->notAMethod();
}

}#