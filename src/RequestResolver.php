<?php
namespace phpgt\fetch;

use GuzzleHttp\Promise\Promise;

/**
 * Contains multiple requests and their promises.
 */
class RequestResolver implements \Iterator {

private $requestArray = [];
private $promiseArray = [];
private $index;

public function __construct() {

}

public function add(Request $request, Promise $promise) {
	$this->requestArray []= $request;
	$this->promiseArray []= $promise;
}

public function current() {
	return $this->promiseArray[$this->index];
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