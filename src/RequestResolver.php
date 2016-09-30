<?php
namespace phpgt\fetch;

use React\Promise\Deferred;

/**
 * Contains multiple requests and their promises.
 */
class RequestResolver implements \Iterator {

private $requestArray = [];
private $deferredArray = [];
private $index;

public function __construct() {

}

public function add(Request $request, Deferred $deferred) {
	$this->requestArray []= $request;
	$this->deferredArray []= $deferred;
}

public function current() {
	return $this->deferredArray[$this->index];
}

public function key() {
	return $this->requestArray[$this->index];
}

public function next() {
	++ $this->index;
}

public function rewind() {
	$this->index = 0;
}

public function valid() {
	return isset($this->requestArray[$this->index]);
}

}#