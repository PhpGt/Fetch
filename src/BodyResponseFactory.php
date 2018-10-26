<?php
namespace Gt\Fetch;

use Gt\Http\Header\ResponseHeaders;
use Gt\Http\Response;

class BodyResponseFactory {
	public static function fromResponse(Response $response):BodyResponse {
		$bodyResponse = new BodyResponse(
			$response->getStatusCode(),
			$response->getResponseHeaders(),
			$response->getBody()
		);

		return $bodyResponse;
	}
}