<?php
require(implode(DIRECTORY_SEPARATOR, ["..", "vendor", "autoload.php"]));

use Gt\Json\JsonKvpObject;
use Gt\Json\JsonPrimitive\JsonArrayPrimitive;
use Gt\Fetch\Http;
use Gt\Fetch\Response\BodyResponse;

// https://api.github.com/orgs/phpgt/repos

$http = new Http();
$http->fetch("https://github.com/PhpGt/Fetch/blob/59a3d1c447fd44d94ea389404c3a2595d98629ce/broken.json")
	->then(
		function (BodyResponse $response) {
			if (!$response->ok) {
				throw new RuntimeException("Can't retrieve Github's API on $response->uri");
			}
			return $response->json()->catch(function (Throwable $reason) {
				echo "ERROR: ", $reason->getMessage(), PHP_EOL, PHP_EOL;
			});
		}
	)
	->then(function (JsonArrayPrimitive $json) {
		echo "SUCCESS: Json promise resolved!", PHP_EOL;
		echo "PHP.Gt has the following repositories: ";
		$repoList = [];
		/** @var JsonKvpObject $item */
		foreach ($json->getPrimitiveValue() as $item) {
			array_push($repoList, $item->getString("name"));
		}
		echo wordwrap(implode(", ", $repoList)) . ".";
		echo PHP_EOL, PHP_EOL;
	})
	->catch(function (Throwable $reason) {
		echo "ERROR: ", $reason->getMessage(), PHP_EOL, PHP_EOL;
	});
$http->wait();