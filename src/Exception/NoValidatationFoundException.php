<?php

namespace GraphQlPhpValidationToolkit\Exception;
class NoValidatationFoundException extends \Exception
{
    public function __construct(string $message = "You must provide at least one 'validate' callback or mark at least one field as 'required'.")
    {
        parent::__construct($message);
    }
}