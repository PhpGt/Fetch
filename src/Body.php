<?php
namespace phpgt\fetch;

use React\Promise\Promise;

use StdClass;

/**
 * Represents the body of the response/request, allowing you to declare what
 * its content type is and how it should be handled.
 *
 * Body is implemented by both Request and Response — this provides these
 * objects with an associated body (a byte stream), a used flag (initially
 * unset), and a MIME type (initially the empty byte sequence).
 */
trait Body {

/**
 * Returns a promise that resolves with an ArrayBuffer containing response data.
 */
public function arrayBuffer():Promise {

}

/**
 * Returns a promise that resolves with a Blob representation of response data.
 */
public function blob():Promise {

}

/**
 * Returns a promise that resolves with a FormData response object.
 */
public function formData():Promise {

}

/**
 * Returns a promise that resolves with a StdClass object containing JSON data.
 */
public function json():Promise {
	// return json_decode($this->body);
}

/**
 * Returns a promise that resolves with a UTF-8 encoded string.
 */
public function text():Promise {
	// $charset = $this->getCharset();
	// $toEncoding = "utf-8";

	// $converted = mb_convert_encoding($this->body, $toEncoding, $charset);
	// return $converted;
}

}#