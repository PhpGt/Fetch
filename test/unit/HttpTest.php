<?php
namespace Gt\Fetch;

use Gt\Fetch\Test\Helper\Helper;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase {

	/**
	 * cURL options can be set at construction time for the Http class that override
	 * the default options required for this library to run.
	 */
	public function testDefaultOptions() {
		$http = new Http();
		$this->assertNotEmpty($http->getOptions());
	}

	public function testDefaultOptionsOverridden() {
		$http = new Http();
		$options = $http->getOptions();
		$this->assertTrue($options[CURLOPT_FOLLOWLOCATION]);

		$options = [
			CURLOPT_FOLLOWLOCATION => false,
		];
		$http = new Http($options);
		$actualOptions = $http->getOptions();
		$this->assertEquals(false, $actualOptions[CURLOPT_FOLLOWLOCATION]);
	}

	public function testEnsureStringUri() {
		$http = new Http();
		$uriToUse = Helper::URI_SIMPLE;
		$request = new Request("GET", $uriToUse);

		$this->assertEquals($uriToUse, $http->ensureUriInterface($request));
		$this->assertEquals($uriToUse, $http->ensureUriInterface($uriToUse));
	}

}