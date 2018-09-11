<?php

namespace Gt\Fetch;

interface GlobalFetch {

	public function fetch($input, array $init = []): Promise;

}