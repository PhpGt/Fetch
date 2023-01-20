<?php
namespace Gt\Fetch\Response;

use RuntimeException;

/**
 * @property-read int $size
 * @property-read string $type
 */
class Blob {
	const ENDINGS_TRANSPARENT = "transparent";
	const ENDINGS_NATIVE = "transparent";
	const PART_TYPE_ARRAY = "array";
	const PART_TYPE_ARRAYBUFFER = "arraybuffer";
	const PART_TYPE_BLOB = "blob";
	const PART_TYPE_STRING = "string";

	private string $type;
//	private string $endings; // TODO: this is currently unused
	protected string $content;

	/**
	 * @param array<string>|ArrayBuffer|Blob|string $blobParts
	 * @param array<string> $options
	 */
	public function __construct(
		array|ArrayBuffer|self|string $blobParts,
		array $options = [],
	) {
		$this->type = $options["type"] ?? "";
//		$this->endings = $options["endings"] ?? self::ENDINGS_TRANSPARENT;

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

	public function __get(string $name):mixed {
		switch($name) {
		case "size":
			return $this->size;

		case "type":
			return $this->type;
		}

		throw new RuntimeException("Undefined property: $name");
	}

	public function getContent():string {
		return $this->content;
	}

	/** @param array<string>|ArrayBuffer|Blob|string $blobParts */
	protected function getBlobPartsType(
		array|ArrayBuffer|self|string $blobParts
	):string {
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
	}

	/** @param array<string> $input */
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

	/** @param iterable<string> $input */
	protected function loadIterable(iterable $input):string {
		$buffer = "";

		foreach($input as $i) {
			$i = str_replace(
				["\n", "\r\n"],
				PHP_EOL,
				$i
			);

			$buffer .= $i;
		}

		return $buffer;
	}
}
