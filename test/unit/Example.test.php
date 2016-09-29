<?php
namespace phpgt\fetch;

require_once __DIR__ . "/../../vendor/autoload.php";

class ExampleTest extends \PHPUnit_Framework_TestCase {

public function testTest() {
	$http = new Http();
	$http->request("test");

	$this->assertTrue(true);
}

}#