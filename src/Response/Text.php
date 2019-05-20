<?php
namespace Gt\Fetch\Response;

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