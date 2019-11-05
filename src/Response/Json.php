<?php
namespace Gt\Fetch\Response;

use ArrayAccess;
use Iterator;
use StdClass;

class Json extends StdClass implements ArrayAccess, Iterator {
	protected $jsonObject;
	protected $iteratorKey;
	protected $iteratorProperties;
	protected $iteratorPropertyNames;

	public function __construct($jsonObject) {
		$this->jsonObject = $jsonObject;
		$this->iteratorKey = 0;
		$this->setPropertiesRecursive();
	}

	public function __toString():string {
		return json_encode($this->jsonObject);
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
		if(is_array($this->jsonObject)) {
			return $this->jsonObject[$offset];
		}

		return $this->jsonObject->$offset;
	}

	/** @link https://php.net/manual/en/arrayaccess.offsetset.php */
	public function offsetSet($offset, $value):void {
		$this->jsonObject->$offset = $value;
	}

	/** @link https://php.net/manual/en/arrayaccess.offsetunset.php */
	public function offsetUnset($offset):void {
		unset($this->jsonObject->$offset);
	}

	/** @link https://php.net/manual/en/iterator.current.php */
	public function current() {
		if(is_array($this->jsonObject)) {
			return $this->jsonObject[$this->iteratorKey];
		}

		$property = $this->iteratorPropertyNames[$this->iteratorKey];
		return $this->jsonObject->$property;
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

		return $this->iteratorPropertyNames[$this->iteratorKey];
	}

	/** @link https://php.net/manual/en/iterator.valid.php */
	public function valid():bool {
		if(!isset($this->iteratorPropertyNames[$this->iteratorKey])) {
			return false;
		}

		$property = $this->iteratorPropertyNames[$this->iteratorKey];
		if(is_array($this->jsonObject)) {
			return isset($this->jsonObject[$this->iteratorKey]);
		}

		return isset($this->jsonObject->{$property});
	}

	/** @link https://php.net/manual/en/iterator.rewind.php */
	public function rewind():void {
		$this->iteratorKey = 0;
	}

	private function setPropertiesRecursive():void {
		foreach($this->jsonObject as $key => $value) {
			if($value instanceof StdClass) {
				$this->iteratorProperties[$key] = new self($value);
			}
			else {
				$this->iteratorProperties[$key] = $value;
			}

			$this->iteratorPropertyNames []= $key;
		}
	}
}