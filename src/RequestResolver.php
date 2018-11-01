<?php
namespace Gt\Fetch;

use Gt\Curl\Curl;
use Gt\Curl\CurlInterface;
use Gt\Curl\CurlMulti;
use Gt\Curl\CurlMultiInterface;
use Gt\Http\Header\Parser;
use Gt\Http\Response;
use Psr\Http\Message\UriInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

class RequestResolver {
	/** @var LoopInterface */
	protected $loop;
	/** @var CurlMultiInterface */
	protected $curlMulti;

	/** @var CurlInterface[] */
	protected $curlList;
	/** @var Deferred[] */
	protected $deferredList;
	/** @var Response[] */
	protected $responseList;
	/** @var string[] */
	protected $headersList;

	public function __construct(
		LoopInterface $loop,
		string $curlMultiClass = CurlMulti::class
	) {
		$this->loop = $loop;
		$this->curlList = [];
		$this->deferredList = [];
		$this->headersList = [];
		$this->curlMulti = new $curlMultiClass();
	}

	public function add(
		UriInterface $uri,
		array $init,
		Deferred $deferred
	):void {
		$curl = new Curl($uri);

		if(!empty($init["curlopt"])) {
			$curl->setOptArray($init["curlopt"]);
		}

		$curl->setOpt(CURLOPT_RETURNTRANSFER, false);
		$curl->setOpt(CURLOPT_HEADER, true);
		$curl->setOpt(CURLOPT_WRITEFUNCTION, [$this, "write"]);

		$this->curlMulti->add($curl);
		$this->curlList []= $curl;
		$this->deferredList []= $deferred;
		$this->headersList []= "";
		$this->responseList []= new Response();
	}

	public function write($chIncoming, $content):int {
		$match = false;
		foreach($this->curlList as $i => $curl) {
			if($chIncoming === $curl->getHandle()) {
				$match = true;
				break;
			}
		}

		if(!$match) {
			// TODO: Throw exception.
			die("NO CURL HANDLE!!!!");
		}

		if(!strstr($this->headersList[$i], "\r\n\r\n")) {
			echo "1";
			if(strstr($content, "\r\n\r\n")) {
				$parser = new Parser($this->headersList[$i]);
				$this->responseList[$i] = $this->responseList[$i]->withProtocolVersion(
					$parser->getProtocolVersion()
				);
				$this->responseList[$i] = $this->responseList[$i]->withStatus(
					$parser->getStatusCode()
				);
				foreach($parser->getKeyValues() as $key => $value) {
					$this->responseList[$i] = $this->responseList[$i]->withAddedHeader(
						$key,
						$value
					);
				}
			}

			$this->headersList[$i] .= $content;
		}
		else {
			echo "2";
			$body = $this->responseList[$i]->getBody();
			$body->write($content);
			$this->deferredList[$i]->resolve($body);
		}

		echo ".";

		return strlen($content);
	}

	public function tick():void {
		$active = 0;

		do {
			$status = $this->curlMulti->exec($active);
		}
		while($status === CURLM_CALL_MULTI_PERFORM);

		if($status !== CURLM_OK) {
			// TODO: Throw exception.
			die("ERROR!");
		}

		if($active === 0) {
			$this->loop->stop();
			return;
		}
	}
}