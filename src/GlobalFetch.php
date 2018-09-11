<?php

namespace Gt\Fetch;

use React\Promise\PromiseInterface;

interface GlobalFetch {
	public function fetch($input, array $init = []):PromiseInterface;
}