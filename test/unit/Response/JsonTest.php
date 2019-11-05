<?php
namespace Gt\Fetch\Test\Response;

use Gt\Fetch\Response\ImmutableObjectModificationException;
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

	public function testGetterNested() {
		$nested = new StdClass();
		$nested->name = new StdClass();
		$nested->name->first = "Margaret";
		$nested->name->last = "Hamilton";
		$nested->job = new StdClass();
		$nested->job->company = "MIT";
		$nested->job->title = "Systems Engineer";

		$sut = new Json($nested);
		self::assertInstanceOf(Json::class, $sut->name);
		self::assertEquals($nested->name->first, $sut->name->first);
		self::assertEquals($nested->name->last, $sut->name->last);
		self::assertEquals($nested->job->company, $sut->job->company);
		self::assertEquals($nested->job->title, $sut->job->title);
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

	public function testAssocArray() {
		$sut = new Json([
			"name" => "Katherine",
			"employer" => "NASA",
		]);
		self::assertEquals("Katherine", $sut->name);
		self::assertEquals("NASA", $sut->employer);
	}

	public function testIsset() {
		$obj = new StdClass();
		$obj->name = "Simon";
		$sut = new Json($obj);
		self::assertTrue(isset($obj->name));
		self::assertFalse(isset($obj->age));
	}

	public function testSet() {
		$sut = new Json(["example"]);
		self::expectException(ImmutableObjectModificationException::class);
		$sut->name = "Test";
	}

	public function testUnset() {
		$sut = new Json(["example"]);
		self::expectException(ImmutableObjectModificationException::class);
		unset($sut->name);
	}
}