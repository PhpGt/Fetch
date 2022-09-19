<?php
require(implode(DIRECTORY_SEPARATOR, ["..", "vendor", "autoload.php"]));

use Gt\Fetch\Http;
use Gt\Fetch\Response\BodyResponse;
use Gt\Json\JsonKvpObject;
use Gt\Json\JsonPrimitive\JsonArrayPrimitive;

// TODO: Remove any reference to REFACTOR_Promise.
// TODO: Make the promises resolve and reject correctly using Gt/Promise/Promise

$http = new Http();
$fetchPromise = $http->fetch("https://github.com/orgs/phpgt/repos");
$jsonPromise = $fetchPromise->then(function(BodyResponse $response) {
	return $response->json();
});

$chainedJsonPromise = $jsonPromise->then(function(JsonArrayPrimitive $json) {
	echo "SUCCESS: Json promise resolved." . PHP_EOL;
	echo "PHP.Gt has the following repositories: ";
	/** @var JsonKvpObject $item */
	foreach($json->getPrimitiveValue() as $i => $item) {
		if($i > 0) {
			echo ", ";
		}
		echo $item->getString("name");
	}
	echo PHP_EOL;
});
$chainedJsonPromise->catch(function(Throwable $reason) {
	echo "ERROR - Json promise rejected with error: " . $reason->getMessage() . PHP_EOL;
	var_dump(debug_backtrace(limit: 3));
});
var_dump($jsonPromise->getState());
// To execute the above Promise(s), call wait() or all().
$http->wait();


var_dump($jsonPromise->getState());
exit;


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
