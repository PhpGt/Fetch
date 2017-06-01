<?php
namespace Gt\Fetch;

use Http\Promise\Promise;

/**
 * @method Promise get(string|Uri $input, array $init = [])
 * @method Promise head(string|Uri $input, array $init = [])
 * @method Promise post(string|Uri $input, array $init = [])
 * @method Promise put(string|Uri $input, array $init = [])
 * @method Promise delete(string|Uri $input, array $init = [])
 * @method Promise connect(string|Uri $input, array $init = [])
 * @method Promise options(string|Uri $input, array $init = [])
 * @method Promise trace(string|Uri $input, array $init = [])
 * @method Promise patch(string|Uri $input, array $init = [])
 */
abstract class GlobalFetchHelper implements GlobalFetch {

const HTTP_METHODS = [
	"GET",
	"HEAD",
	"POST",
	"PUT",
	"DELETE",
	"CONNECT",
	"OPTIONS",
	"TRACE",
	"PATCH",
];

public function __call($name, $args) {
	$method = strtoupper($name);
	if(!in_array($method, self::HTTP_METHODS)) {
		trigger_error(
			"Call to undefined method "
			. __CLASS__
			. "::"
			. $name
			. "()"
			,
			E_USER_ERROR
		);
	}

	$init = $args[1] ?? [];
	$init["method"] = $method;
	$args[1] = $init;

	return call_user_func_array([$this, "fetch"], $args);
}

}#