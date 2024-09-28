<?php

namespace GraphQlPhpValidationToolkit\Type\Definition;

class UserErrorsNonNullType extends UserErrorsType
{
    public function __construct(array $config, array $path, bool $isParentList = false)
    {
        $fields = [];
        parent::__construct($config, $path, $isParentList);
    }
}
