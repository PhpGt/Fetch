<?php
namespace Gt\Fetch;

class HttpTest extends \PHPUnit_Framework_TestCase {

/**
 * cURL options can be set at construction time for the Http class that override
 * the default options required for this library to run.
 */
public function testDefaultOptions() {
	$http = new Http();
	$this->assertNotEmpty($http->getOptions());
}

}#