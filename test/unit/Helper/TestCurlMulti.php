<?php
namespace Gt\Fetch\Test\Helper;

use Gt\Curl\CurlInterface;
use Gt\Curl\CurlMulti;

class TestCurlMulti extends CurlMulti {
	protected $ch;

	public function add(CurlInterface $curl):void {
		$this->ch = $curl;
	}

	public function exec(int &$stillRunning):int {
		if(!ResponseSimulator::hasStarted()) {
			ResponseSimulator::start();
		}

		$stillRunning += ResponseSimulator::sendChunk($this->ch);
		return CURLM_OK;
	}
}