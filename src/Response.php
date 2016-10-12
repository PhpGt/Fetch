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

const STREAM_TARGET_HEADERS = "HEADERS";
const STREAM_TARGET_BODY = "BODY";

/** @var int Can only be accessed via the __get, as per the fetch API. */
private $statusCode;
/** @var Headers */
private $headers;
private $rawBody = "";
/** @var array */
private $curlInfo;

/** @var Deferred[] */
private $readRawBodyDeferredArray = [];
/** @var callable[] */
private $readRawBodyDeferredTransformArray = [];

/** @var Deferred */
private $deferredResponse;
private $streamTarget = self::STREAM_TARGET_HEADERS;

public function __construct(Deferred $deferredResponse, LoopInterface $loop) {
	$this->headers = new Headers();
	$this->deferredResponse = $deferredResponse;
}

public function __get($name) {
	switch($name) {
	case "status":
		return $this->statusCode;
        break;

    default:
        trigger_error("Undefined property: "
            . __CLASS__
            . "::\$"
            . $name
            , E_USER_NOTICE
        );
        return null;
    }
}

public function stream($curlHandle, string $data):int {
	$bytesRead = strlen($data);

    if($this->streamTarget === self::STREAM_TARGET_HEADERS) {
        if ($this->setHeaders($data) !== true) {
            $this->streamTarget = self::STREAM_TARGET_BODY;
            $this->curlInfo = curl_getinfo($curlHandle);
            $this->deferredResponse->resolve($this);
        }
    }

    if($this->streamTarget === self::STREAM_TARGET_BODY) {
        $this->rawBody .= $data;

        foreach ($this->readRawBodyDeferredArray as $readRawBodyDeferred) {
            if (is_callable([$readRawBodyDeferred, "notify"])) {
                $readRawBodyDeferred->notify($data);
            }
        }
    }

	return $bytesRead;
}

/**
 * Called when the underlying curl handle completes. At this point, all of the
 * response has arrived, so we can resolve any functions reading the body.
 *
 * Each deferred reader has its own transform function to manupulate the body
 * into the expected format, which is called within the same iteration.
 *
 * @param   $statusCode  int    The HTTP status code of the completed response
 */
public function complete(int $statusCode) {
    $this->statusCode = $statusCode;
	foreach($this->readRawBodyDeferredArray as $i => $readRawBodyDeferred) {
		$readRawBodyDeferred->resolve(
			$this->readRawBodyDeferredTransformArray[$i]($this->rawBody)
		);
	}
}

public function redirect() {
// TODO: Implement redirect()
    throw new \Exception("Method not yet implemented");
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

		$this->headers->append($header, $value);
	}

	return true;
}

/**
 * Parses provided header string, returning KVP array.
 * Code taken from http://php.net/manual/bg/function.http-parse-headers.php
 * as not all systems will have PECL HTTP extension installed.
 *
 * @param   $rawHeaders string  The raw headers to be parsed
 * @return  array               An array of headers
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
// TODO: Implement clone()
    throw new \Exception("Method not yet implemented");
}

public function error() {
// TODO: Implement error()
    throw new \Exception("Method not yet implemented");
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
	&& $this->headers->has("Content-Type")) {
		$contentTypeString = $this->headers->get("Content-Type");
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
