<?php
namespace phpgt\fetch;

/**
 * @property-read bool $bodyUsed Indicates whether the body has been read yet
 * @property-read Headers $headers Contains the Headers object associated
 * with the response
 * @property-read bool $ok Whether the response was successful (status in
 * the range 200-299) or not
 * @property-read int $status The status code of the response
 * @property-read string $statusText The status message corresponding to the
 * status code
 * @property-read string $type Type of the response (basic, cors, error
 * or opaque)
 * @property-read string $url The URL of the response
 *
 */
class Response {

private $curlInfo;
private $headers = [];
private $body;

public function __construct(string $rawResponse, array $curlInfo) {
	$headerSize = $curlInfo["header_size"];
	$headerString = substr($rawResponse, 0, $headerSize);
	foreach(explode("\n", $headerString) as $line) {
		$kvp = explode(":", $line);
		if(!isset($kvp[1])) {
			continue;
		}

		$this->headers[trim($kvp[0])] = trim($kvp[1]);
	}

	$this->body = substr($rawResponse, $headerSize);
	$this->curlInfo = $curlInfo;
}

}#