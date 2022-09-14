<?php
require(implode(DIRECTORY_SEPARATOR, ["..", "vendor", "autoload.php"]));

use Gt\Fetch\Http;
use Gt\Fetch\Response\BodyResponse;
use Gt\Json\JsonKvpObject;
use Gt\Json\JsonObject;
use Gt\Json\JsonPrimitive\JsonArrayPrimitive;

/*
 * This example fetches the list of repositories in the PhpGt organisation from
 * Github's public API.
 */

$http = new Http();
$http->fetch("https://api.github.com/orgs/phpgt/repos")
->then(function(BodyResponse $response) {
	if(!$response->ok) {
		echo "Error fetching Github's API.";
		exit(1);
	}

	return $response->json();
})
->then(function(JsonArrayPrimitive $json) {
// $json is a pre-decoded object. Expected response is an array of Repositories,
// as per https://developer.github.com/v3/repos/#list-organization-repositories
	echo "PHP.Gt repository list:" . PHP_EOL;

	/** @var JsonKvpObject $repo */
	foreach($json->getPrimitiveValue() as $repo) {
		echo $repo->getString("name") . PHP_EOL;
	}
}, function(Throwable $exception) {
	echo "There was an error: " . $exception->getMessage() . PHP_EOL;
});

// To execute the above Promise(s), call wait() or all().
$http->wait();
