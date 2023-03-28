<?php
namespace Gt\Fetch\Test\Helper;

use CurlHandle;
use Gt\Curl\Curl;

class TestCurl extends Curl {
	protected $id;

	public function __construct(string $url = null) {
		$this->id = uniqid("dummy-curl-");
		parent::__construct($url);
	}

	public function setOpt(int $option, $value):bool {
		if($option === CURLOPT_HEADERFUNCTION) {
			ResponseSimulator::setHeaderCallback($value);
		}

		if($option === CURLOPT_WRITEFUNCTION) {
			ResponseSimulator::setBodyCallback($value);
		}

		return true;
	}

	public function getHandle():CurlHandle {
		return $this->ch;
	}
}
