<?php
namespace Gt\Fetch\Test\Helper;

use Gt\Curl\CurlInterface;
use Gt\Curl\CurlMulti;

class TestCurlMulti extends CurlMulti {
	protected CurlInterface $curl;

	public function add(CurlInterface $curl):void {
		$this->curl = $curl;
	}

	public function exec(int &$stillRunning):int {
		if(!ResponseSimulator::hasStarted()) {
			ResponseSimulator::start();
		}

		$stillRunning += ResponseSimulator::sendChunk($this->curl->getHandle());
		return CURLM_OK;
	}
}
