<?php
namespace Gt\Fetch;

class UriTest extends \PHPUnit_Framework_TestCase {

public function testSimpleUrl() {
	$uri = new Uri(test\Helper::URI_SIMPLE);

	$this->assertEquals("fake", $uri->getScheme());
	$this->assertEquals("php.gt", $uri->getAuthority());
	$this->assertEquals("", $uri->getUserInfo());
	$this->assertEquals("php.gt", $uri->getHost());
	$this->assertNull($uri->getPort());
	$this->assertEquals("/fetch", $uri->getPath());
	$this->assertEquals("", $uri->getQuery());
	$this->assertEquals("", $uri->getFragment());
}

public function testComplexUrl() {
	$uri = new Uri(test\Helper::URI_COMPLEX);

	$this->assertEquals("fake", $uri->getScheme());
	$this->assertEquals("someuser:somepassword@php.gt:8008", $uri->getAuthority());
	$this->assertEquals("someuser:somepassword", $uri->getUserInfo());
	$this->assertEquals("php.gt", $uri->getHost());
	$this->assertEquals(8008, $uri->getPort());
	$this->assertEquals("/fetch", $uri->getPath());
	$this->assertEquals("id=105", $uri->getQuery());
	$this->assertEquals("example", $uri->getFragment());
}

}#