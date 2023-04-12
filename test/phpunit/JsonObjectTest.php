<?php

// use PHPUnit\Framework\TestCase;
// use Gt\Json\JsonObject;
// use Gt\Json\JsonDecodeException;

// class JsonObjectTest extends TestCase
// {
// public function testFromJsonString(): void
// {
//     $jsonObject = new JsonObject();

//     // Test with valid JSON
//     $validJsonString = '{"foo":"bar","baz":[1,2,3]}';
//     $expected = [
//         'foo' => 'bar',
//         'baz' => [1, 2, 3]
//     ];
//     $result = $jsonObject->fromJsonString($validJsonString);
//     $this->assertEquals($expected, $result->toArray());

//     // Test with invalid JSON
//     $invalidJsonString = '{"foo":"bar","baz":';
//     $this->expectException(JsonDecodeException::class);
//     $jsonObject->fromJsonString($invalidJsonString);
// }

// public function testFromJsonStringWithValidJsonString(): void
// {
//     $jsonString = '{"name": "John", "age": 30, "city": "New York"}';
//     $expectedObject = new JsonObject(["name" => "John", "age" => 30, "city" => "New York"]);

//     $jsonObject = $this->jsonObjectFactory->fromJsonString($jsonString);

//     $this->assertInstanceOf(JsonObject::class, $jsonObject);
//     $this->assertEquals($expectedObject, $jsonObject);
// }

// public function testFromJsonStringWithValidJsonStringAndCustomDepth(): void
// {
//     $jsonString = '{"name": "John", "age": 30, "city": {"name": "New York", "population": 8000000}}';
//     $expectedObject = new JsonObject([
//         "name" => "John",
//         "age" => 30,
//         "city" => new JsonObject(["name" => "New York", "population" => 8000000])
//     ]);

//     $jsonObject = $this->jsonObjectFactory->fromJsonString($jsonString, depth: 2);

//     $this->assertInstanceOf(JsonObject::class, $jsonObject);
//     $this->assertEquals($expectedObject, $jsonObject);
// }

// public function testFromJsonStringWithValidJsonStringAndFlags(): void
// {
//     $jsonString = '{"name": "John", "age": 30, "city": "New York"}';
//     $expectedObject = new JsonObject(["name" => "John", "age" => 30, "city" => "New York"]);

//     $jsonObject = $this->jsonObjectFactory->fromJsonString($jsonString, flags: JSON_OBJECT_AS_ARRAY);

//     $this->assertInstanceOf(JsonObject::class, $jsonObject);
//     $this->assertEquals($expectedObject, $jsonObject);
// }

//     public function testFromJsonStringWithInvalidJsonString(): void
//     {
//         $jsonString = '{name: John, age: 30, city: New York}';

//         $this->expectException(JsonDecodeException::class);
//         $this->jsonObjectFactory->fromJsonString($jsonString);
//     }

// }
use Gt\Curl\Curl;
use Gt\Async\Loop;
use Gt\Json\JsonObject;
use PHPUnit\Framework\TestCase;
use Gt\Json\JsonDecodeException;
use Gt\Json\NativeJsonException;
use Gt\Fetch\Response\BodyResponse;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\MockObject\MockObject;

// class JsonObjectTest extends TestCase
// {
//     /**
//      * @dataProvider provideJsonString
//      */
//     public function testFromJsonString(string $jsonString, string $expectedException = null)
//     {
//         if ($expectedException) {
//             $this->expectException($expectedException);
//         }

//         $json = new class extends JsonObject {
//             public function fromJsonDecoded(object $json): JsonObject
//             {
//                 // Do nothing in this test implementation
//                 return $this;
//             }
//         };

//         $result = $json->fromJsonString($jsonString);

//         if (!$expectedException) {
//             $this->assertInstanceOf(JsonObject::class, $result);
//         }
//     }

//     public function provideJsonString(): array
//     {
//         return [
//             ['{"key": "value"}'],
//             ['{"key": "value", "array": [1, 2, 3]}'],
//             ['{"key": "value", "nested": {"inner": true}}'],
//             ['{"key": "value", "invalid": }', JsonDecodeException::class],
//         ];
//     }
// }

class JsonObjectTest extends TestCase
{
    public function testInvalidJson()
    {
        $jsonString = "{'this;': is not valid JSON'}";

        $loop = self::createMock(Loop::class);
        $stream = self::createMock(StreamInterface::class);
        /** @var MockObject|StreamInterface $stream */
        $stream = self::createMock(StreamInterface::class);
        $stream->method("tell")
            ->willReturn(0);
        $stream->method("getContents")
            ->willReturn($jsonString);
        /** @var MockObject|Curl $curl */
        $curl = self::createMock(Curl::class);

        $sutOutput = null;
        $sutError = null;
        $sut = new BodyResponse();
        $sut = $sut->withBody($stream);
        $sut->startDeferredResponse($loop, $curl);
        $promise = $sut->json();
        $promise
            ->then(function ($fulfilledValue) use (&$sutOutput) {
                $sutOutput = $fulfilledValue;
            })
            ->catch(function ($errorValue) use (&$sutError) {
                $sutError = $errorValue;
            });
        $sut->endDeferredResponse();

        self::assertNull($sutOutput);
        self::assertInstanceOf(JsonDecodeException::class, $sutError);
    }

// public function testFromJsonStringReturnsJsonObjectWithValidJson(): void
// {
//     $jsonString = '{"foo": "bar"}';
//     $json = new JsonObject();
//     $result = $json->fromJsonString($jsonString);

//     $this->assertInstanceOf(JsonObject::class, $result);
// }
}