<?php
namespace Gt\Fetch\Test\Helper;

use Gt\Curl\Curl;

class TestCurl extends Curl {
	protected string $id;

	public function __construct(string $url = null) {
		$this->id = uniqid("dummy-curl-");
		$this->ch = curl_init();
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

	public function getHandle() {
		return $this;
	}
}
