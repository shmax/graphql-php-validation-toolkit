<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

class NonNullErrorType extends ErrorType
{
    public function __construct(array $config, array $path)
    {
        parent::__construct($config, $path);
    }
}
