<?php

namespace GraphQlPhpValidationToolkit\Type\Definition;

class UserErrorsListItemType extends UserErrorsType
{
    protected function __construct()
    {
        parent::__construct([], ["simple"], true);
    }
}