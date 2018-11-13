<?php
namespace Gt\Fetch;

use Gt\Curl\Curl;
use Gt\Curl\CurlInterface;
use Gt\Curl\CurlMulti;
use Gt\Curl\CurlMultiInterface;
use Gt\Http\Header\Parser;
use Psr\Http\Message\UriInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\Promise as ReactPromise;

class RequestResolver {
	/** @var LoopInterface */
	protected $loop;

	/** @var CurlMultiInterface[] */
	protected $curlMultiList;
	/** @var CurlInterface[] */
	protected $curlList;
	/** @var ReactPromise[] */
	protected $deferredList;
	/** @var BodyResponse[] */
	protected $responseList;
	/** @var string[] */
	protected $headerList;

	public function __construct(LoopInterface $loop) {
		$this->loop = $loop;
		$this->curlList = [];
		$this->curlMultiList = [];
		$this->deferredList = [];
		$this->headerList = [];
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
		$curl->setOpt(CURLOPT_HEADER, false);
		$curl->setOpt(CURLOPT_HEADERFUNCTION, [$this, "writeHeader"]);
		$curl->setOpt(CURLOPT_WRITEFUNCTION, [$this, "write"]);

		$curlMulti = new CurlMulti();
		$curlMulti->add($curl);

		$this->curlList []= $curl;
		$this->curlMultiList []= $curlMulti;
		$this->deferredList []= $deferred;
		$bodyResponse = new BodyResponse();
		$bodyResponse->startDeferredResponse($this->loop);
		$this->responseList []= $bodyResponse;
		$this->headerList []= "";
	}

	public function write($ch, $content):int {
		$i = $this->getIndex($ch);

		$body = $this->responseList[$i]->getBody();
		$body->write($content);

		if($this->deferredList[$i]) {
			$this->deferredList[$i]->resolve($this->responseList[$i]);
			$this->deferredList[$i] = null;
		}

		return strlen($content);
	}

	public function writeHeader($ch, string $rawHeader) {
		$i = $this->getIndex($ch);
		$headerLine = trim($rawHeader);

		if($headerLine === "") {
			$parser = new Parser($this->headerList[$i]);
			$this->responseList[$i] = $this->responseList[$i]->withProtocolVersion(
				$parser->getProtocolVersion()
			);
			$this->responseList[$i] = $this->responseList[$i]->withStatus(
				$parser->getStatusCode()
			);
			foreach($parser->getKeyValues() as $key => $value) {
				if(empty($key)) {
					continue;
				}
				$this->responseList[$i] = $this->responseList[$i]->withAddedHeader(
					$key,
					$value
				);
			}
		}

		$this->headerList[$i] .= $rawHeader;
		return strlen($rawHeader);
	}

	public function tick():void {
		$totalActive = 0;

		foreach($this->curlMultiList as $i => $curlMulti) {
			$active = 0;

			do {
				$status = $curlMulti->exec($active);
			}
			while($status === CURLM_CALL_MULTI_PERFORM);

			if($status !== CURLM_OK) {
				// TODO: Throw exception.
				die("ERROR!");
			}

			$totalActive += $active;

			if($active === 0) {
				if($this->responseList[$i]) {
					$this->responseList[$i]->endDeferredResponse();
					$this->responseList[$i] = null;
				}
			}
		}

		if($totalActive === 0) {
			$this->loop->stop();
			return;
		}
	}

	protected function getIndex($chIncoming):int {
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

		return $i;
	}
}