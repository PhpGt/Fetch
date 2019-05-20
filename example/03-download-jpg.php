<?php
require(implode(DIRECTORY_SEPARATOR, ["..", "vendor", "autoload.php"]));

use Gt\Fetch\Http;
use Gt\Fetch\Response\Blob;
use Gt\Fetch\Response\BodyResponse;

/*
 * This example uses Cat-as-a-Service API to request a random photo of a cat.
 * See https://cataas.com/ for more information.
 */

// Example: Download an image from the API and store in the temp directory.

$http = new Http();
$http->fetch("https://cataas.com/cat")
->then(function(BodyResponse $response) {
	if(!$response->ok) {
		echo "Error getting a cat." . PHP_EOL;
		exit(1);
	}

	echo "Cat as a Service responded with a binary file with the following attributes:" . PHP_EOL;
	echo "Response size: " . $response->getHeaderLine("Content-Length") . PHP_EOL;
	echo "File type: " . $response->getHeaderLine("Content-Type") . PHP_EOL;
	return $response->blob();
})
->then(function(Blob $blob) {
	$extension = null;

	switch($blob->type) {
	case "image/jpeg":
		$extension = "jpg";
		break;

	case "image/png":
		$extension = "png";
		break;

	default:
		echo $blob->type . " type is not supported." . PHP_EOL;
		exit(1);
	}

	$file = new SplFileObject("/tmp/cat.$extension", "w");
	$bytesWritten = $file->fwrite($blob);
	echo "Written $bytesWritten bytes." . PHP_EOL;
});

// To execute the above Promise(s), call wait() or all().
$http->wait();