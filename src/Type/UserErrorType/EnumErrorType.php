<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;

class EnumErrorType extends ErrorType
{
    protected function __construct(array $config, array $path)
    {
        if (!isset($config['validate']) && empty($config['required'])) {
            throw new NoValidatationFoundException();
        }
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