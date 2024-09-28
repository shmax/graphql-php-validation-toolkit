<?php

namespace GraphQlPhpValidationToolkit\Type\Definition;

use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;

class UserErrorsListOfType extends UserErrorsType
{
    protected function __construct(array $config, array $path, bool $isParentList)
    {
        parent::__construct($config, $path, $isParentList);
        $fields = [];
        $this->_addItemsErrorField($fields, $config, $path);
    }

    protected function _addItemsErrorField(array &$fields, array $config, array $path): void
    {
        $type = $this->_resolveType($config['type'], true);
        try {
            $errorType = static::create([
                'type' => $type,
            ], array_merge($path, [$type->name]));

            $this->config['fields']['items'] = [
                'name' => 'items',
                'type' => Type::listOf($errorType),
                'description' => 'Validation errors for each ' . $type->name . ' in the list',
                'resolve' => static function ($value) {
                    return $value['items'] ?? null;
                },
            ];
        }
        catch (NoValidatationFoundException $e) {
            if(!isset($config['validateItem']) && !isset($config['validate'])) {
                throw new NoValidatationFoundException("You must specify at least one 'validate' or 'validateItem' callback somewhere in the tree.");
            }
            else if(isset($config['validateItem'])) {
                $this->config['fields']['items'] = [
                    'name' => 'items',
                    'type' => Type::listOf(static::listItemType()),
                    'description' => 'Validation errors for each ' . $type->name . ' in the list',
                    'resolve' => static function ($value) {
                        return $value['items'] ?? null;
                    },
                ];
            }
        }
    }
}