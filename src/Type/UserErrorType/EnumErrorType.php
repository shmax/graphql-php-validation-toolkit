<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

class EnumErrorType extends ErrorType
{
    static protected function empty(mixed $value): bool
    {
        return parent::empty($value) || strlen($value) === 0;
    }

    protected function _validate(array $arg, mixed $value, array &$res): void
    {

    }
}