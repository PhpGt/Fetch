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
		$curl->setOpt(CURLOPT_WRITEFUNCTION, [$this, "writeBody"]);

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

	public function writeHeader($ch, string $rawHeader) {
		$i = $this->getIndex($ch);
		$headerLine = trim($rawHeader);

// If $headerLine is empty, it represents the last line before the body starts.
// HTTP headers always end on an empty line. See https://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html
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

// To indiciate that this function has successfully run, cURL expects it to
// return the number of bytes read. If this does not match the same number
// that cURL sees, cURL will drop the connection.
		return strlen($rawHeader);
	}

	public function writeBody($ch, $content):int {
		$i = $this->getIndex($ch);

		$body = $this->responseList[$i]->getBody();
		$body->write($content);

		if($this->deferredList[$i]) {
			$this->deferredList[$i]->resolve($this->responseList[$i]);
			$this->deferredList[$i] = null;
		}

// To indiciate that this function has successfully run, cURL expects it to
// return the number of bytes read. If this does not match the same number
// that cURL sees, cURL will drop the connection.
		return strlen($content);
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
		$i = -1;
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