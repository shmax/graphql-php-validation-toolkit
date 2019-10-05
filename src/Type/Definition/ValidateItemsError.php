<?php
namespace GraphQL\Type\Definition;

class ValidateItemsError extends \Exception {
	public $path;
	public $error;
	function __construct($path, $error) {
		parent::__construct();
		$this->path = $path;
		$this->error = $error;
	}
}