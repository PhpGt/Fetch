<?php
namespace Gt\Fetch;

use Gt\Async\Loop;
use Gt\Curl\CurlInterface;
use Gt\Curl\CurlMultiInterface;
use Gt\Fetch\Response\BodyResponse;
use Gt\Http\Header\Parser;
use Gt\Promise\Deferred;
use Psr\Http\Message\UriInterface;

class RequestResolver {
	protected Loop $loop;

	/** @var array<CurlMultiInterface> */
	protected array $curlMultiList;
	/** @var array<CurlInterface> */
	protected array $curlList;
	/** @var array<Deferred> */
	protected array $deferredList;
	/** @var array<BodyResponse> */
	protected array $responseList;
	/** @var array<string> */
	protected array $headerList;
	/** @var array<string> */
	protected array $integrityList;
	/** @var array<object> */
	protected array $signalList;

	public function __construct(
		Loop $loop,
		private readonly string $curlClass,
		private readonly string $curlMultiClass,
	) {
		$this->loop = $loop;
		$this->curlMultiList = [];
		$this->curlList = [];
		$this->deferredList = [];
		$this->responseList = [];
		$this->headerList = [];
		$this->integrityList = [];
		$this->signalList = [];
	}

	public function add(
		UriInterface $uri,
		array $curlOptArray,
		Deferred $deferred,
		string $integrity = null,
		Controller $signal = null
	):void {
		/** @var CurlInterface $curl */
		$curl = new $this->curlClass($uri);

// curlopt1: Set the default curlopt values here:
		$curl->setOpt(CURLOPT_USERAGENT, Http::USER_AGENT);

// curlopt2: Then override any curlopt values that are provided:
		if(!empty($curlOptArray)) {
			$curl->setOptArray($curlOptArray);
		}

// curlopt3: Finally, hard-code these curlopt settings:
		$curl->setOpt(CURLOPT_RETURNTRANSFER, false);
		$curl->setOpt(CURLOPT_HEADER, false);
		$curl->setOpt(CURLOPT_HEADERFUNCTION, $this->writeHeader(...));
		$curl->setOpt(CURLOPT_WRITEFUNCTION, $this->writeBody(...));
		$curl->setOpt(CURLOPT_PROGRESSFUNCTION, $this->progress(...));
		$curl->setOpt(CURLOPT_NOPROGRESS, false);

		/** @var CurlMultiInterface $curlMulti */
		$curlMulti = new $this->curlMultiClass();
		$curlMulti->add($curl);

		$this->loop->addDeferredToTimer($deferred);

		array_push($this->curlList, $curl);
		array_push($this->curlMultiList, $curlMulti);
		array_push($this->deferredList, $deferred);
		array_push($this->integrityList, $integrity);
		$bodyResponse = new BodyResponse();
		$bodyResponse->startDeferredResponse($this->loop, $curl);
		array_push($this->responseList, $bodyResponse);
		array_push($this->headerList, "");
		array_push($this->signalList, $signal);
	}

	public function writeHeader($ch, string $rawHeader):int {
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

// To indicate that this function has successfully run, cURL expects it to
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

// To indicate that this function has successfully run, cURL expects it to
// return the number of bytes read. If this does not match the same number
// that cURL sees, cURL will drop the connection.
		return strlen($content);
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function progress(
		$ch,
		int $expectedDownloadedBytes,
		int $downloadedBytes,
		int $expectedUploadedBytes,
		int $uploadedBytes
	):int {
		$index = $this->getIndex($ch);
		return (int)$this->signalList[$index]?->aborted;

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
				$this->responseList[$i]?->endDeferredResponse(
					$this->integrityList[$i]
				);
				if($this->deferredList[$i]) {
					$this->deferredList[$i]->resolve($this->responseList[$i]);
				}

				$this->responseList[$i] = null;
				$this->deferredList[$i] = null;
			}
		}

		if($totalActive === 0) {
			$this->loop->halt();
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
