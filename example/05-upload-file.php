<?php
require(implode(DIRECTORY_SEPARATOR, ["..", "vendor", "autoload.php"]));

use Gt\Fetch\Http;
use Gt\Http\FormData;
use Gt\Http\Response;
use Gt\Json\JsonObject;

$formData = new FormData();
$formData->set("upload", new SplFileObject(__FILE__));

$http = new Http();
$http->fetch("https://postman-echo.com/post", [
	"method" => "POST",
	"headers" => [
		"Content-type" => "multipart/form-data"
	],
	"body" => $formData,
])
	->then(function(Response $response) {
		if(!$response->ok) {
			throw new RuntimeException("Error uploading file to Postman Echo.");
		}
		return $response->json();
	})
	->then(function(JsonObject $json) {
		foreach($json->asArray()["files"] as $fileName => $data) {
			echo $fileName . " - " . strlen($data) . " bytes", PHP_EOL;
		}
	})
	->catch(function(Throwable $error) {
		echo "An error occurred: ", $error->getMessage();
	});

$http->wait();
die("done waiting");
