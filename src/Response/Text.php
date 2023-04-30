<?php
namespace Gt\Fetch\Response;

/**
 * This class represents a string response. The reason it wraps a string with an
 * object is to allow for the same code to construct any type of response, and
 * in turn means that the Gt\Fetch\Response namespace is a good reference for
 * the types of response possible.
 */
class Text {
	/** @var string */
	protected $content;

	public function __construct(string $content) {
		$this->content = $content;
	}

	public function __toString():string {
		return $this->content;
	}
}
