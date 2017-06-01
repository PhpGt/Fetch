<?php

namespace Gt\Fetch;

use Http\Promise\Promise;

interface GlobalFetch {

public function fetch($input, array $init = []) : Promise;

}#