<?php
namespace Gt\Fetch\Response;

use ArrayAccess;
use Iterator;
use StdClass;

class Json extends StdClass implements ArrayAccess, Iterator {
	protected $jsonObject;
	protected $iteratorKey;
	protected $iteratorProperties;

	public function __construct($jsonObject) {
		$this->jsonObject = $jsonObject;

		$this->iteratorKey = 0;

		if(is_array($jsonObject)) {
			$this->iteratorProperties = array_keys($jsonObject);
		}
		else {
			$this->iteratorProperties = get_object_vars($jsonObject);
		}

	}

	public function __get(string $key) {
		return $this->jsonObject->$key;
	}

	/** @link https://php.net/manual/en/arrayaccess.offsetexists.php */
	public function offsetExists($offset):bool {
		return isset($this->jsonObject[$offset]);
	}

	/** @link https://php.net/manual/en/arrayaccess.offsetget.php */
	public function offsetGet($offset) {
		return $this->jsonObject[$offset];
	}

	/** @link https://php.net/manual/en/arrayaccess.offsetset.php */
	public function offsetSet($offset, $value):void {
		$this->jsonObject[$offset] = $value;
	}

	/** @link https://php.net/manual/en/arrayaccess.offsetunset.php */
	public function offsetUnset($offset):void {
		unset($this->jsonObject[$offset]);
	}

	/** @link https://php.net/manual/en/iterator.current.php */
	public function current() {
		if(is_array($this->jsonObject)) {
			return $this->jsonObject[$this->iteratorKey];
		}

		$property = $this->iteratorProperties[$this->iteratorKey];
		return $this->jsonObject->{$property};
	}

	/** @link https://php.net/manual/en/iterator.next.php */
	public function next():void {
		$this->iteratorKey++;
	}

	/** @link https://php.net/manual/en/iterator.key.php */
	public function key() {
		if(is_array($this->jsonObject)) {
			return $this->iteratorKey;
		}

		return $this->iteratorProperties[$this->iteratorKey];
	}

	/** @link https://php.net/manual/en/iterator.valid.php */
	public function valid() {
		if(is_array($this->jsonObject)) {
			return isset($this->jsonObject[$this->iteratorKey]);
		}

		$property = $this->iteratorProperties[$this->iteratorKey];
		return isset($this->jsonObject->{$property});
	}

	/** @link https://php.net/manual/en/iterator.rewind.php */
	public function rewind() {
		$this->iteratorKey = 0;
	}
}