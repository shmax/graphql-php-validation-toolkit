<?php

namespace GraphQlPhpValidationToolkit\Exception;
class NoValidatationFoundException extends \Exception
{
    public function __construct(string $message = "You must specify at least one 'validate' callback somewhere in the tree.")
    {
        parent::__construct($message);
    }
}