<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

class UserErrorsNonNullType extends UserErrorsType
{
    public function __construct(array $config, array $path)
    {
        $fields = [];
        parent::__construct($config, $path);
    }
}
