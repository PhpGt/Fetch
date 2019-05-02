<?php
namespace Gt\Fetch;

use Gt\Http\RequestMethod;
use Psr\Http\Message\RequestInterface;

/**
 * Converts a Fetch Init array to CURLOPT_* key-values.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/WindowOrWorkerGlobalScope/fetch#Syntax
 */
class CurlOptBuilder {
	protected $curlOptArray;

	public function __construct($input, array $init = []) {
		$this->curlOptArray = [];

		if($input instanceof RequestInterface) {
			$this->setFromRequestObject($input);
		}

		foreach($init as $key => $value) {
			$function = "set" . ucfirst($key);
			if(method_exists($this, $function)) {
				call_user_func([$this, $function], $value);
			}
			else {
				throw new UnknownCurlOptException($key);
			}
		}
	}

	public function asCurlOptArray():array {
		return $this->curlOptArray;
	}

	protected function setFromRequestObject(RequestInterface $request) {
		$this->curlOptArray[CURLOPT_URL] = (string)$request->getUri();

		if($method = $request->getMethod()) {
			$this->setMethod($method);
		}

		$this->setHeaders($request->getHeaders());
	}

	/**
	 * @param string $value The request method, e.g., GET, POST.
	 */
	protected function setMethod(string $value) {
		$this->curlOptArray[CURLOPT_CUSTOMREQUEST] =
			RequestMethod::filterMethodName($value);
	}

	/**
	 * @param array $headers Any headers you want to add to your request,
	 * contained within an associative array.
	 */
	protected function setHeaders(array $headers):void {
		$rawHeaders = [];

		foreach($headers as $key => $value) {
			$headerLine = "$key: ";
			if(!is_array($value)) {
				$value = [$value];
			}

			$headerLine .= implode(", ", $value);
			$rawHeaders []= $headerLine;
		}

		$this->curlOptArray[CURLOPT_HTTPHEADER] = $rawHeaders;
	}

	/**
	 * @param string $body Any body that you want to add to your request:
	 * this can be a string, associative array (form data) or binary object.
	 */
	protected function setBody($body):void {
		$this->curlOptArray[CURLOPT_POSTFIELDS] = $body;
	}

	/**
	 * @param string $value The mode you want to use for the request,
	 * e.g., cors, no-cors, or same-origin
	 */
	protected function setMode(string $value):void {
// TODO: Is this even possible to configure server-side?
	}

	/**
	 * @param string $value The request credentials you want to use for the
	 * request: omit, same-origin, or include. To automatically send
	 * cookies for the current domain, this option must be provided.
	 */
	protected function setCredentials(string $value):void {
// TODO: Throw exception until cookies are implemented.
	}

	/**
	 * @param string $value The cache mode you want to use for the request.
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/Request/cache
	 */
	protected function setCache(string $value):void {
// TODO: Throw exception until caching is implemented.
	}

	/**
	 * @param string $value The redirect mode to use: follow (automatically
	 * follow redirects) or manual (handle redirects manually).
	 */
	protected function setRedirect(string $value):void {
		$this->curlOptArray[CURLOPT_FOLLOWLOCATION] =
			strtolower($value) === "follow";
	}

	/**
	 * @param string $value A string specifying no-referrer, client,
	 * or a URL.
	 */
	protected function setReferrer(string $value):void {
		switch(strtolower($value)) {
		case "no-referrer":
			$this->curlOptArray[CURLOPT_REFERER] = null;
			break;

		case "client":
// TODO: What is the significance of "client".
			break;

		default:
			$this->curlOptArray[CURLOPT_REFERER] = $value;
			break;
		}
	}

	/**
	 * @param string $value Specifies the value of the referer HTTP header.
	 * May be one of no-referrer, no-referrer-when-downgrade, origin,
	 * origin-when-cross-origin, unsafe-url.
	 */
	protected function setReferrerPolicy(string $value):void {
// TODO: Need to understand how this works on the server.
	}

	/**
	 * @param string $value Contains the subresource integrity value of the
	 * request (e.g., sha256-BpfBw7ivV8q2jLiT13fxDYAe2tJllusRSZ273h2nFSE=).
	 */
	protected function setIntegrity(string $value):void {
// TODO: Set a value to later check on the request, throwing exception on mismatch.
	}

	protected function setKeepalive(string $value):void {
		$this->curlOptArray[CURLOPT_TCP_KEEPALIVE] = (int)$value;
	}

	protected function setSignal(string $value):void {
// TODO: Allow passing an AbortController to cancel a fetch early.
	}
}