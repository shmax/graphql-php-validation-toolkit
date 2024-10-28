<?php

namespace GraphQlPhpValidationToolkit\Exception;
class OverlySpecializedValidationErrorType extends \Exception
{
    public function __construct(string $message = "The validation error type currently in use can be downgraded to a simpler one.")
    {
        parent::__construct($message);
    }
}