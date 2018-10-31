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
	protected $loop;

	/** @var CurlInterface[] */
	protected $curlReferenceList;
	/** @var Deferred[] */
	protected $deferredReferenceList;
	/** @var Response[] */
	protected $responseReferenceList;

	/** @var CurlMultiInterface */
	protected $curlMulti;

	public function __construct(
		LoopInterface $loop,
		string $curlMultiClass = CurlMulti::class
	) {
		$this->loop = $loop;
		$this->curlReferenceList = [];
		$this->deferredReferenceList = [];
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

		$curl->setOpt(CURLOPT_RETURNTRANSFER, true);
		$curl->setOpt(CURLOPT_HEADER, true);

		$this->curlMulti->add($curl);
		$this->curlReferenceList []= $curl;
		$this->deferredReferenceList []= $deferred;
		$this->responseReferenceList []= new Response();
	}

	public function tick():void {
		$active = 0;

		ob_start();
		$curlMultiCode = $this->curlMulti->exec($active);
		if($curlMultiCode !== CURLM_OK) {
			// TODO: Throw exception.
			die("ERROR!");
		}
		ob_end_clean();

		if($state = $this->curlMulti->infoRead()) {
			$ch = $state->getHandle();

			foreach($this->curlReferenceList as $i => $curlReference) {
				if($ch !== $curlReference) {
					continue;
				}

				$content = $this->curlMulti->getContent(
					$ch
				);
				$response = $this->responseReferenceList[$i];

				list(
					$headerString,
					$bodyString
				) = explode(
					"\r\n\r\n",
					$content
				);


				$headerParser = new Parser($headerString);
				$response = $response->withProtocolVersion(
					$headerParser->getProtocolVersion()
				);
				$response = $response->withStatus(
					$headerParser->getStatusCode()
				);

				foreach($headerParser->getKeyValues() as $key => $value) {
					$response = $response->withAddedHeader(
						$key,
						$value
					);
				}

// Note: Updating the original reference, as $response is immutable.
				$this->responseReferenceList[$i] = $response;
				$body = $response->getBody();

				if($response->getStatusCode()) {
					$body->write($bodyString);
				}

				$this->deferredReferenceList[$i]->resolve(
					BodyResponseFactory::fromResponse($response)
				);
			}
		}
	}
}