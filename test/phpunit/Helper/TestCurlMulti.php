<?php
namespace Gt\Fetch\Test\Helper;

use Gt\Curl\CurlInterface;
use Gt\Curl\CurlMulti;

class TestCurlMulti extends CurlMulti {
	protected array $curlHandleArray;

	public function add(CurlInterface $curl):void {
		array_push($this->curlHandleArray, $curl);
	}

	public function exec(int &$stillRunning):int {
		if(!ResponseSimulator::hasStarted()) {
			ResponseSimulator::start();
		}

		foreach($this->curlHandleArray as $ch) {
			$stillRunning += ResponseSimulator::sendChunk($ch);
		}
		return CURLM_OK;
	}
}
