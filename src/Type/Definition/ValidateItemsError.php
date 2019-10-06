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

    /**
     * ValidateItemsError constructor.
     * @param int[] $path
     * @param mixed $error
     */
    public function __construct(array $path, $error)
    {
        parent::__construct();
        $this->path  = $path;
        $this->error = $error;
    }
}
