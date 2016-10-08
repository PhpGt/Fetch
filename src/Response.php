<?php
namespace phpgt\fetch;

use React\Promise\Deferred;
use React\EventLoop\LoopInterface;

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
use Body;

/** @var Headers */
private $headers;
private $rawHeaders = "";
private $rawBody = "";

/** @var React\Promise\Deferred[] */
private $readRawBodyDeferredArray = [];

/** @var React\Promise\Deferred */
private $deferredResponse;
private $isHeaderStreaming;

public function __construct(Deferred $deferredResponse, LoopInterface $loop) {
	$this->headers = new Headers();
	$this->deferredResponse = $deferredResponse;
}

public function stream($curlHandle, string $data):int {
	$bytesRead = strlen($data);

	if(is_null($this->isHeaderStreaming)) {
		$this->isHeaderStreaming = true;
	}

	if($this->isHeaderStreaming) {
		$this->rawHeaders .= $data;
		$this->isHeaderStreaming = $this->setHeaders($data);

		if(!$this->isHeaderStreaming) {
			$this->deferredResponse->resolve($this);
		}
	}
	else {
		$this->rawBody .= $data;

		foreach($this->readRawBodyDeferredArray as $readRawBodyDeferred) {
			if(is_callable([$readRawBodyDeferred, "notify"])) {
				$readRawBodyDeferred->notify($data);
			}
		}

	}

	return $bytesRead;
}

/**
 * Called when the underlying curl handle completes. At this point, all of the
 * response has arrived, so we can resolve any functions reading the body.
 */
public function complete(int $statusCode) {
	foreach($this->readRawBodyDeferredArray as $readRawBodyDeferred) {
		$readRawBodyDeferred->resolve($this->rawBody);
	}
}

public function redirect() {

}

/**
 * Parses the raw header(s) provided as a string to this function and sets them
 * using the Headers object.
 *
 * @param string $rawHeader The unprocessed HTTP header string, direct from the
 * web server
 *
 * @return bool True if the header(s) have been set, false if the header section
 * has ended
 */
private function setHeaders(string $rawHeader):bool {
	foreach($this->parseHeaders($rawHeader) as $header => $value) {
		if(empty($header)
		&& empty($value)) {
			return false;
		}

		$this->headers->set($header, $value);
	}

	return true;
}

/**
 * Parses provided header string, returning KVP array.
 * Code taken from http://php.net/manual/bg/function.http-parse-headers.php
 * as not all systems will have PECL HTTP extension installed.
 */
private function parseHeaders(string $rawHeaders):array {
	$headers = [];
	$key = "";

	foreach(explode("\n", $rawHeaders) as $i => $h) {
		$h = explode(":", $h, 2);

		if(isset($h[1])) {
			if(!isset($headers[$h[0]])) {
				$headers[$h[0]] = trim($h[1]);
			}
			else if(is_array($headers[$h[0]])) {
				$headers[$h[0]] = array_merge(
					$headers[$h[0]],
					array(trim($h[1]))
				);
			}
			else {
				$headers[$h[0]] = array_merge(
					array($headers[$h[0]]),
					array(trim($h[1]))
				);
			}

			$key = $h[0];
		}
		else {
			if(substr($h[0], 0, 1) === "\t") {
				$headers[$key] .= "\r\n\t" . trim($h[0]);
			}
			else if(!$key) {
				if(empty($headers[0])) {
					$headers[0] = trim($h[0]);
				}
			}
		}
	}

	return $headers;
}


public function clone() {

}

public function error() {

}

/**
 * Extracts and returns the character set value from the Content-Type header
 * value of $this object, if set. If not set, or none is found, the default
 * encoding type is returned.
 *
 * @return string The character set used to store body text.
 */
private function getCharset():string {
	$charset = mb_internal_encoding();

	if(isset($this->headers)
	&& isset($this->headers["Content-Type"])) {
		$contentTypeString = $this->headers["Content-Type"];
		$charsetEquals = "charset=";

		if(!strstr($contentTypeString, $charsetEquals)) {
			return $charset;
		}

		$charset = substr(
			$contentTypeString,
			strpos($contentTypeString, $charsetEquals) + strlen($charsetEquals)
		);

		$delimiterPosition = strpos($charset, ";");
		if($delimiterPosition > 0) {
			$charset = substr($charset, 0, $delimiterPosition);
		}
	}

	return $charset;
}

}#