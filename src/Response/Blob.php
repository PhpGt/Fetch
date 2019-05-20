<?php
namespace Gt\Fetch\Response;

/**
 * @property-read int size
 * @property-read string type
 */
class Blob {
	const ENDINGS_TRANSPARENT = "transparent";
	const ENDINGS_NATIVE = "transparent";
	const PART_TYPE_ARRAY = "array";
	const PART_TYPE_ARRAYBUFFER = "arraybuffer";
	const PART_TYPE_BLOB = "blob";
	const PART_TYPE_STRING = "string";

	protected $type;
	protected $endings;
	/** @var string */
	protected $content;

	public function __construct($blobParts, array $options = []) {
		$this->type = $options["type"] ?? "";
		$this->endings = $options["endings"] ?? self::ENDINGS_TRANSPARENT;

		$partType = $this->getBlobPartsType($blobParts);

		switch($partType) {
		case self::PART_TYPE_ARRAY:
			$this->content = $this->loadArray($blobParts);
			break;

		case self::PART_TYPE_ARRAYBUFFER:
			$this->content = $this->loadArrayBuffer($blobParts);
			break;

		case self::PART_TYPE_BLOB:
			$this->content = $this->loadBlob($blobParts);
			break;

		case self::PART_TYPE_STRING:
			$this->content = $this->loadString($blobParts);
			break;
		}
	}

	public function __toString():string {
		return $this->getContent();
	}

	public function __get(string $key) {
		switch($key) {
		case "size":
			return $this->size;
			break;

		case "type":
			return $this->type;
			break;
		}
	}

	public function getContent():string {
		return $this->content;
	}

	protected function getBlobPartsType($blobParts):string {
		$type = null;

		if(is_array($blobParts)) {
			return self::PART_TYPE_ARRAY;
		}

		if($blobParts instanceof ArrayBuffer) {
			return self::PART_TYPE_ARRAYBUFFER;
		}

		if($blobParts instanceof self) {
			return self::PART_TYPE_BLOB;
		}

		if(is_string($blobParts)) {
			return self::PART_TYPE_STRING;
		}

		if(is_null($type)) {
			throw new InvlidBlobPartTypeException($blobParts);
		}
	}

	protected function loadArray(array $input):string {
		return $this->loadIterable($input);
	}

	protected function loadArrayBuffer(ArrayBuffer $input):string {
		return $this->loadIterable($input);
	}

	protected function loadBlob(self $input):string {
		return $input->content;
	}

	protected function loadString(string $input):string {
		return $input;
	}

	protected function loadIterable(iterable $input):string {
		$buffer = "";

		foreach($input as $i) {
			if(self::ENDINGS_NATIVE) {
				$i = str_replace(
					["\n", "\r\n"],
					PHP_EOL,
					$i
				);
			}

			$buffer .= $i;
		}

		return $buffer;
	}
}