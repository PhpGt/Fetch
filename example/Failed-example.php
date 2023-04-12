<?php

require(implode(DIRECTORY_SEPARATOR, ["..", "vendor", "autoload.php"]));

use Gt\Fetch\Http;
use Gt\Fetch\Response\Blob;
use Gt\Fetch\Response\BodyResponse;
use Gt\Json\JsonKvpObject;
use Gt\Json\JsonPrimitive\JsonArrayPrimitive;

$http = new Http();

$http->fetch("https://github.com/Kibet-mutai/Talent-search/blob/main/composer.json")
    ->then(function (BodyResponse $response) {
        if (!$response->ok) {
            throw new RuntimeException("Can't retrieve Github's API on $response->uri");
        }
        return $response->json();
    })
    ->then(function ($json) {
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Error parsing JSON: " . json_last_error_msg());
        }

        echo "SUCCESS: Json promise resolved!", PHP_EOL;
        echo "PHP.Gt has the following repositories: ";
        $repoList = [];
        /** @var JsonKvpObject $item */
        foreach ($json as $item) {
            array_push($repoList, $item->getString("name"));
        }

        echo wordwrap(implode(", ", $repoList)) . ".";
        echo PHP_EOL, PHP_EOL;
    })
    ->catch(function (Throwable $reason) {
        echo "ERROR: ", PHP_EOL, $reason->getMessage(), PHP_EOL, PHP_EOL;
    });
$http->wait();