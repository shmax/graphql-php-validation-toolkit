<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

class NonNullErrorType extends ErrorType
{
    public function __construct(array $config, array $path)
    {
        parent::__construct($config, $path);
    }

    protected function _validate(array $arg, mixed $value, array &$res): void
    {

    }
}
