<?php
namespace Gt\Fetch;

use Http\Promise\Promise as HttpPromise;

interface GlobalFetch {
	public function fetch($input, array $init = []):HttpPromise;
}