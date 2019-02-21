<?php
require(implode(DIRECTORY_SEPARATOR, ["..", "vendor", "autoload.php"]));

use Gt\Fetch\Http;
use Gt\Fetch\BodyResponse;

/*
 * This example uses postman-echo.com do test the request/response.
 * See https://docs.postman-echo.com/ for more information.
 */

// Example: Post form data to the echo server.

$http = new Http();
$http->fetch("https://postman-echo.com/post", [
// All of the request parameters can be passed directly here, or alternatively
// the fetch() function can take a PSR-7 RequestInterface object.
	"method" => "POST",
	"headers" => [
		"Content-Type" => "application/x-www-form-urlencoded",
	],
	"body" => http_build_query([
		"name" => "Mark Zuckerberg",
		"dob" => "1984-05-14",
		"email" => "zuck@fb.com",
	]),
])
->then(function(BodyResponse $response) {
	if(!$response->ok) {
		echo "Error posting to Postman Echo." . PHP_EOL;
		exit;
	}
// Postman Echo servers respond with a JSON representation of the request
// that was received.
	return $response->json();
})
->then(function($json) {
	echo "The Postman Echo server received the following form fields:";
	echo PHP_EOL;

	foreach($json->form as $key => $value) {
		echo "$key = $value" . PHP_EOL;
	}
});

// To execute the above Promise(s), call wait() or all().
$http->wait();