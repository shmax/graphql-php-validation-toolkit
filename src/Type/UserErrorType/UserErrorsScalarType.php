<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;

class UserErrorsScalarType extends UserErrorsType
{
    protected function __construct(array $config, array $path)
    {
        if(!isset($config['validate'])) {
            throw new NoValidatationFoundException();
        }
        parent::__construct($config, $path);
    }
}