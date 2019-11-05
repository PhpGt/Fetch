<?php
namespace Gt\Fetch\Response;

use ArrayAccess;
use DateTime;
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

	/** @return self|int|bool|string|float */
	public function __get(string $key) {
		return $this->offsetGet($key);
	}

	public function __set(string $key, $value) {
		$this->offsetSet($key, $value);
	}

	public function __unset(string $key) {
		$this->offsetUnset($key);
	}

	public function __isset(string $key):bool {
		return $this->offsetExists($key);
	}

	/** @link https://php.net/manual/en/arrayaccess.offsetexists.php */
	public function offsetExists($offset):bool {
		return isset($this->iteratorProperties[$offset]);
	}

	/**
	 * @return self|int|bool|string|float
	 * @link https://php.net/manual/en/arrayaccess.offsetget.php
	 */
	public function offsetGet($offset) {
		if(is_array($this->jsonObject) && isset($this->jsonObject[0])) {
			return $this->jsonObject[$offset];
		}

		return $this->iteratorProperties[$offset] ?? null;
	}

	/** @link https://php.net/manual/en/arrayaccess.offsetset.php */
	public function offsetSet($offset, $value):void {
		throw new ImmutableObjectModificationException();
	}

	/** @link https://php.net/manual/en/arrayaccess.offsetunset.php */
	public function offsetUnset($offset):void {
		throw new ImmutableObjectModificationException();
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

	public function getBool(string $key):?bool {
		$value = $this->offsetGet($key);
		if(is_null($value)) {
			return null;
		}

		return (bool)$value;
	}

	public function getString(string $key):?string {
		$value = $this->offsetGet($key);
		if(is_null($value)) {
			return null;
		}

		return (string)$value;
	}

	public function getInt(string $key):?int {
		$value = $this->offsetGet($key);
		if(is_null($value)) {
			return null;
		}

		return (int)$value;
	}

	public function getFloat(string $key):?float {
		$value = $this->offsetGet($key);
		if(is_null($value)) {
			return null;
		}

		return (float)$value;
	}

	public function getDateTime(string $key):?DateTime {
		$value = $this->offsetGet($key);
		if(is_null($value)) {
			return null;
		}

		return new DateTime($value);
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