<?php
namespace Gt\Fetch\Test\Helper;

class ResponseSimulator {
	const RANDOM_BODY_WORDS = ["pursuit","forest","gravel","timber","wonder","eject","slogan","monkey","construct","earthquake","respect","publish","forward","circle","summer","define","highlight","refuse","salon","theater","lily","earwax","variant","account","resource"];
	static protected $headerCallback;
	static protected $bodyCallback;
	static protected $headerBuffer;
	static protected $bodyBuffer;
	static protected $started = false;

	static public function setHeaderCallback(callable $callback) {
		self::$headerCallback = $callback;
	}

	static public function setBodyCallback(callable $callback) {
		self::$bodyCallback = $callback;
	}

	static public function start() {
		self::$started = true;
		self::$headerBuffer = self::generateRandomHeaders();
		self::$bodyBuffer = self::generateRandomBody();
	}

	static protected function generateRandomHeaders():array {
		$headers = [];

		$headers []= "HTTP/0.0 999 OK";
		$headers []= "Date: " . date("D, d M Y H:i:s T");
		$headers []= "Repository: PhpGt/Fetch";

		$length = rand(1, 10);
		for($i = 0; $i < $length; $i++) {
			$randIndex = array_rand(self::RANDOM_BODY_WORDS);
			$key = self::RANDOM_BODY_WORDS[$randIndex];
			$value = uniqid();
			$headers []= "$key: $value";
		}

		foreach($headers as $i => $h) {
			$headers[$i] .= "\r\n";
		}

		$headers []= "\r\n";

		return $headers;
	}

	static protected function generateRandomBody():string {
		$body = "";
		$length = rand(10, 100);
		for($i = 0; $i < $length; $i++) {
			$randIndex = array_rand(self::RANDOM_BODY_WORDS);
			$body .= self::RANDOM_BODY_WORDS[$randIndex];
			$body .= " ";
		}

		return $body;
	}

	static public function hasStarted():bool {
		return self::$started;
	}

	static public function sendChunk($ch):int {
		if(!empty(self::$headerBuffer)) {
			$data = array_shift(self::$headerBuffer);

			call_user_func(
				self::$headerCallback,
				$ch,
				$data
			);

			return 1;
		}
		elseif(!empty(self::$bodyBuffer)) {
			$data = self::$bodyBuffer;
			self::$bodyBuffer = "";

			call_user_func(
				self::$bodyCallback,
				$ch,
				$data
			);

			return 1;
		}
		else {
			return 0;
		}
	}
}