<?php
namespace Gt\Fetch;

use Gt\Curl\JsonDecodeException;
use Gt\Http\Response;
use React\Promise\Deferred;
use React\Promise\Promise;

class BodyResponse extends Response {
	public function arrayBuffer():Promise {
		$deferred = new Deferred();

		return $deferred->promise();
	}

	public function blob():Promise {
		$deferred = new Deferred();
		return $deferred->promise();
	}

	public function formData():Promise {
		$deferred = new Deferred();
		return $deferred->promise();
	}

	public function json(int $depth = 512, int $options = 0):Promise {
		$deferred = new Deferred();

		$json = json_decode(
			$this->getBody(),
			false,
			$depth,
			$options
		);
		if(is_null($json)) {
			$errorMessage = json_last_error_msg();
			throw new JsonDecodeException($errorMessage);
		}

		$deferred->resolve($json);

		return $deferred->promise();
	}

	public function text():Promise {

		var_dump($this->getBody()->read(100));die();
		$deferred = new Deferred();
		$deferred->resolve((string)$this->getBody());
		return $deferred->promise();
	}
}