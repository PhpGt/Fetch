<?php
namespace Gt\Fetch;

class AbortController extends Controller {
	public $signal;
	public $aborted;

	public function __construct() {
		$this->signal = $this;
		$this->aborted = false;
	}

	public function abort():void {
		$this->aborted = true;
	}
}