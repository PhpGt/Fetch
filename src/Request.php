<?php
namespace phpgt\fetch;

class Request {

const METHOD_GET = "get";
const METHOD_HEAD = "head";
const METHOD_POST = "post";
const METHOD_PUT = "put";
const METHOD_DELETE = "delete";
const METHOD_TRACE = "trace";
const METHOD_OPTIONS = "options";
const METHOD_CONNECT = "connect";
const METHOD_PATCH = "patch";
const AVAILABLE_METHODS = [
	self::METHOD_GET,
	self::METHOD_HEAD,
	self::METHOD_POST,
	self::METHOD_PUT,
	self::METHOD_DELETE,
	self::METHOD_TRACE,
	self::METHOD_OPTIONS,
	self::METHOD_CONNECT,
	self::METHOD_PATCH,
];

const REDIRECT_FOLLOW = "follow";
const REDIRECT_ERROR = "error";
const REDIRECT_MANUAL = "manual";
const AVAILABLE_REDIRECTS = [
	self::REDIRECT_FOLLOW,
	self::REDIRECT_ERROR,
	self::REDIRECT_MANUAL,
];

const CREDENTIAL_OMIT = "omit";
const CREDENTIAL_SAME_ORIGIN = "same-origin";
const CREDENTIAL_INCLUDE = "include";

const AVAILABLE_CREDENTIALS = [
	self::CREDENTIAL_OMIT,
	self::CREDENTIAL_SAME_ORIGIN,
	self::CREDENTIAL_INCLUDE,
];

private $method;
private $headers;
private $body;
private $credentials;
private $redirect;
private $referrer;
private $integrity;

/**
 * @var PHPCurl\CurlWrapper\Curl
 */
private $curl;

/**
 * @param string $uri Direct URI of the object to be fetched
 * @param array $init Optional associative array of options
 * @param array $curlOpt Optional associative array of curl_setopt settings
 */
public function __construct(string $uri, array $init = [], array $curlOpt = [],
string $curlClass = "\PHPCurl\CurlWrapper\Curl") {
	$method = self::METHOD_GET;
	if(!empty($init["method"])) {
		$method = strtolower($init["method"]);

		if(!in_array($method, self::AVAILABLE_METHODS)) {
			throw new HttpMethodException($method);
		}

	}
	$this->method = $method;

	if(!empty($init["headers"])
	&& is_array($init["headers"])) {
		$this->headers = $init["headers"];
	}

	if(!empty($init["body"])) {
		if(($method !== self::METHOD_GET || $method !== self::METHOD_HEAD)) {
			throw new HttpInitException("body");
		}

		$this->body = $init["body"];
	}

	if(!empty($init["credentials"])) {
		if(!in_array(
		strtolower($init["credentials"]), self::AVAILABLE_CREDENTIALS)) {
			throw new HttpInitException("credentials");
		}
	}

	if(!empty($init["redirect"])) {
		if(!in_array(
		strtolower($init["redirect"]), self::AVAILABLE_REDIRECTS)) {
			throw new HttpInitException("redirect");
		}

		$this->redirect = $init["redirect"];
	}

	if(!empty($init["referrer"])) {
		if(!is_string($init["referrer"])) {
			throw new HttpInitException("referrer");
		}

		$this->referrer = $init["referrer"];
	}

	if(!empty($init["integrity"])) {
		if(!is_string($init["integrity"])) {
			throw new HttpInitException("integrity");
		}

		$this->integrity = $init["integrity"];
	}

	$this->curl = new $curlClass($uri);
	$this->curlInit($curlOpt);
}

public function getCurlHandle() {
	return $this->curl;
}

public function getResponseCode():int {
	return (int)$this->curl->getInfo(CURLINFO_HTTP_CODE);
}

public function getResponse() {
	$response = new Response();
	die("NOT YET IMPLEMENTED");
}

private function curlInit($options = []) {
	$defaultOptions = [];

	$defaultOptions[CURLOPT_CUSTOMREQUEST] = $this->method;

	if(isset($this->headers)) {
		$defaultOptions[CURLOPT_HTTPHEADER] = $this->headers;
	}

	if(isset($this->body)) {
		$defaultOptions[CURLOPT_POSTFIELDS] = $this->body;
	}
// TODO: Set up cookie jar for $this->credentials
// as described https://developer.mozilla.org/en-US/docs/Web/API/GlobalFetch/fetch

	if($this->redirect === self::REDIRECT_FOLLOW) {
		$defaultOptions[CURLOPT_FOLLOWLOCATION] = true;
	}
	else {
		$defaultOptions[CURLOPT_FOLLOWLOCATION] = false;
	}

	if(isset($this->referrer)) {
		$defaultOptions[CURLOPT_REFERER] = $this->referrer;
	}

	$options = array_merge($defaultOptions, $options);

// The returntransfer option MUST be set, otherwise the promise resolution
// callbacks will not be able to get the content of the HTTP requests.
	$options[CURLOPT_RETURNTRANSFER] = true;

	$this->curl->setOptArray($options);
}

}#