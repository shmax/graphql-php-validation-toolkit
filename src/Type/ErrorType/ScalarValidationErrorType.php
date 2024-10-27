<?php

namespace GraphQlPhpValidationToolkit\Type\ErrorType;

use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;

class ScalarValidationErrorType extends ValidationErrorType
{
    protected function __construct(array $config, array $path)
    {
        if (!isset($config['validate']) && empty($config['required'])) {
            throw new NoValidatationFoundException();
        }
        parent::__construct($config, $path);
    }

    protected function _validate(array $arg, mixed $value, array &$res): void
    {

    }
}