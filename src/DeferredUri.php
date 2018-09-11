<?php
namespace Gt\Fetch;

use Psr\Http\Message\UriInterface;
use React\Promise\Deferred;

class DeferredUri {
	protected $uri;
	protected $deferred;

	public function __construct(UriInterface $uri, Deferred $deferred) {
		$this->uri = $uri;
		$this->deferred = $deferred;
	}

	public function getUri():UriInterface {
		return $this->uri;
	}

	public function getDeferred():Deferred {
		return $this->deferred;
	}
}