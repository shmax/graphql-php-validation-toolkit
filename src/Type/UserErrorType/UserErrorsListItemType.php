<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

class UserErrorsListItemType extends UserErrorsType
{
    protected function __construct()
    {
        parent::__construct([], ["simple"], true);
    }
}