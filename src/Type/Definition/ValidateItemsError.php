<?php

declare(strict_types=1);

namespace GraphQL\Type\Definition;

use Exception;

class ValidateItemsError extends Exception
{
    /** @var int[] */
    public $path;

    /** @var mixed */
    public $error;
    public function __construct($path, $error)
    {
        parent::__construct();
        $this->path  = $path;
        $this->error = $error;
    }
}
