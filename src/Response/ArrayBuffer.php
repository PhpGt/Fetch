<?php
namespace Gt\Fetch\Response;

use RuntimeException;
use SplFixedArray;

/**
 * @property-read int $byteLength
 * @extends SplFixedArray<string>
 */
class ArrayBuffer extends SplFixedArray {
	public function __get(string $name):mixed {
		switch($name) {
		case "byteLength":
			return count($this);
		}

		throw new RuntimeException("Undefined property: $name");
	}

	/**
	 * @SuppressWarnings("UnusedFormalParameter")
	 * @noinspection PhpUnusedParameterInspection
	 */
	// phpcs:ignore
	public function transfer(
		self $oldBuffer,
		int $newByteLength = null
	):self {
		return $this;
	}

	/**
	 * @SuppressWarnings("UnusedFormalParameter")
	 * @noinspection PhpUnusedParameterInspection
	 */
	// phpcs:ignore
	public function slice(
		int $begin,
		int $end,
	):self {
		return $this;
	}
}
