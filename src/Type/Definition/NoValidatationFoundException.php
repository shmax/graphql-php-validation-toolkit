<?php

namespace GraphQL\Type\Definition;
class NoValidatationFoundException extends \Exception
{
    public function __construct()
    {
        parent::__construct("You must specify at least one 'validate' or 'validateItem' callback somewhere in the tree.");
    }
}