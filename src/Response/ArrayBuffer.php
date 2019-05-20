<?php
namespace Gt\Fetch\Response;

use SplFixedArray;

/**
 * @property-read int byteLength
 */
class ArrayBuffer extends SplFixedArray {
	public function transfer(
		self $oldBuffer,
		int $newByteLength = null
	):self {

	}

	public function slice(int $begin, int $end):self {

	}
}