<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

class StringErrorType extends ScalarErrorType
{
    protected function __construct(array $config, array $path)
    {
        parent::__construct($config, $path);
    }

    static protected function empty(mixed $value): bool
    {
        return parent::empty($value) || strlen($value) === 0;
    }

    protected function _validate(array $arg, mixed $value, array &$res): void
    {

    }
}