<?php

namespace GraphQL\Type\Definition;

class UserErrorsListOfType extends UserErrorsType
{
    protected function __construct(array $config, array $path)
    {
        $fields = [];

        if (! empty($config['validateItem'])) {
            $this->_addItemsErrorField($fields, $config, $path);
        }

        parent::__construct($config, $path);
    }

    protected function _addItemsErrorField(array &$fields, array $config, array $path): void
    {
        $fields['items'] = [
            'type' => Type::listOf(new ObjectType([
                'name' => $this->_nameFromPath(array_merge($path, ['items'])),
                'description' => 'Validation errors for items.',
                'fields' => $fields,
            ])),
            'description' => 'Validation errors for items in the list.',
            'resolve' => static function ($value) {
                return $value['items'] ?? null;
            },
        ];
    }
}