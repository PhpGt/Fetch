<?php
require(implode(DIRECTORY_SEPARATOR, ["..", "vendor", "autoload.php"]));

use Gt\Fetch\Http;
use Gt\Fetch\Response\BodyResponse;
use Gt\Json\JsonKvpObject;
use Gt\Json\JsonPrimitive\JsonArrayPrimitive;

$getJsonFromResponse = function(BodyResponse $response) {
	if(!$response->ok) {
		throw new RuntimeException("Can't retrieve Github's API on $response->uri");
	}

	return $response->json();
};
$listReposFromJson = function(JsonArrayPrimitive $json) {
	echo "SUCCESS: Json promise resolved!", PHP_EOL;
	echo "PHP.Gt has the following repositories: ";
	$repoList = [];
	/** @var JsonKvpObject $item */
	foreach($json->getPrimitiveValue() as $item) {
		array_push($repoList, $item->getString("name"));
	}

	echo wordwrap(implode(", ", $repoList)) . ".";
	echo PHP_EOL, PHP_EOL;
};
$errorHandler = function(Throwable $reason) {
	echo "ERROR: ", PHP_EOL, $reason->getMessage(), PHP_EOL, PHP_EOL;
};

$http = new Http();

$http->fetch("https://api.github.com/orgs/phpgt/repos")
	->then($getJsonFromResponse)
	->then($listReposFromJson)
	->catch($errorHandler);

$http->fetch("https://github.com/phpgt/__this-does-not-exist")
	->then($getJsonFromResponse)
	->then($listReposFromJson)
	->catch($errorHandler);

$http->fetch("https://raw.githubusercontent.com/PhpGt/Fetch/4729aa197fde1276eeaba8e75188c2979afbb250/broken.json")
	->then($getJsonFromResponse)
	->then($listReposFromJson)
	->catch($errorHandler);

$http->wait();
