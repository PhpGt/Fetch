<?php
namespace Gt\Fetch\Test\Response;

use Gt\Fetch\Response\Json;
use PHPUnit\Framework\TestCase;
use stdClass;

class JsonTest extends TestCase {
	public function testGetterSimple() {
		$simple = new StdClass();
		$simple->name = "Mark";
		$simple->company = "Facebook";

		$sut = new Json($simple);
		self::assertEquals($simple->name, $sut->name);
		self::assertEquals($simple->company, $sut->company);
	}

	public function testArrayAccessSimple() {
		$simple = new StdClass();
		$simple->name = "Marissa";
		$simple->company = "Yahoo";

		$sut = new Json($simple);
		self::assertEquals($simple->name, $sut["name"]);
		self::assertEquals($simple->company, $sut["company"]);
	}

	public function testArrayAccessArray() {
		$array = ["Bread", "Beans", "Milk", "Coffee"];
		$sut = new Json($array);

		foreach($array as $i => $value) {
			self::assertEquals($value, $sut[$i]);
		}
	}

	public function testToStringSimple() {
		$simple = new StdClass();
		$simple->name = "Sundar";
		$simple->company = "Google";

		$sut = new Json($simple);
		self::assertEquals(json_encode($simple), (string)$sut);
	}

	public function testToStringArray() {
		$array = ["Bread", "Beans", "Milk", "Coffee"];
		$sut = new Json($array);
		self::assertEquals(json_encode($array), (string)$sut);
	}

	public function testIteratorSimple() {
		$simple = new StdClass();
		$simple->name = "Satya";
		$simple->company = "Microsoft";

		$sut = new Json($simple);
		$i = 0;
		foreach($sut as $key => $value) {
			$i++;
			self::assertEquals($simple->$key, $value);
		}


		self::assertEquals(count(get_object_vars($simple)), $i);
	}

	public function testIteratorArray() {
		$array = ["Bread", "Beans", "Milk", "Coffee"];
		$sut = new Json($array);

		foreach($sut as $i => $value) {
			self::assertEquals($array[$i], $value);
		}
		self::assertEquals(count($array) - 1, $i);
	}
}