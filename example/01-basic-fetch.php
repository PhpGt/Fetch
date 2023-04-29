<?php
require(implode(DIRECTORY_SEPARATOR, ["..", "vendor", "autoload.php"]));

use Gt\Fetch\Http;
use Gt\Fetch\Response\Blob;
use Gt\Fetch\Response\FetchResponse;
use Gt\Json\JsonKvpObject;
use Gt\Json\JsonPrimitive\JsonArrayPrimitive;

$http = new Http();

$http->fetch("https://api.github.com/orgs/phpgt/repos")
//$http->fetch("https://raw.githubusercontent.com/PhpGt/Fetch/master/broken.json")
	->then(function(FetchResponse $response) {
		if(!$response->ok) {
			throw new RuntimeException("Can't retrieve Github's API on $response->uri");
		}

		return $response->json();
	})
	->then(function(JsonArrayPrimitive $json) {
		echo "SUCCESS: Json promise resolved!", PHP_EOL;
		echo "PHP.Gt has the following repositories: ";
		$repoList = [];
		/** @var JsonKvpObject $item */
		foreach($json->getPrimitiveValue() as $item) {
			array_push($repoList, $item->getString("name"));
		}

		echo wordwrap(implode(", ", $repoList)) . ".";
		echo PHP_EOL, PHP_EOL;
	})
	->catch(function(Throwable $reason) {
		echo "Here's the error: ", PHP_EOL, $reason->getMessage(), " ", $reason->getFile(), ":", $reason->getLine(), PHP_EOL, PHP_EOL;
	});

$http->wait();
