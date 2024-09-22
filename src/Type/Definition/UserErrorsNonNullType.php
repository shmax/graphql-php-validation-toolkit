<?php

namespace GraphQL\Type\Definition;

class UserErrorsNonNullType extends UserErrorsType
{
    public function __construct(array $config, array $path)
    {
        $fields = [];
        parent::__construct($config, $fields, $path);
    }

    protected function _buildTypeFields(Type $type, array $config, array $path): array
    {
        return []; // NonNull specific logic
    }
}
