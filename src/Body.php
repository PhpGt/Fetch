<?php
namespace phpgt\fetch;

/**
 * Represents the body of the response/request, allowing you to declare what
 * its content type is and how it should be handled.
 *
 * Body is implemented by both Request and Response — this provides these
 * objects with an associated body (a byte stream), a used flag (initially
 * unset), and a MIME type (initially the empty byte sequence).
 */
trait Body {

public function arrayBuffer() {

}

public function blob() {

}

public function clone() {

}

public function error() {

}

public function formData() {

}

public function json() {

}

public function redirect() {

}

public function text() {

}

}#