<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
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

            if($type instanceof ScalarType) {
                $errorType = static::create([
                    'type' => $type,
                    'validate' => $type->config['validate'] ?? null
                ], [$this->name, $type->name]);
            }
            else {
                $errorType = static::create([
                    'type' => $type,
                ], array_merge($path, [$type->name]));
            }

            $this->config['fields']['items'] = [
                'name' => 'items',
                'type' => Type::listOf($errorType),
                'description' => 'Validation errors for each ' . $type->name() . ' in the list',
                'resolve' => static function ($value) {
                    return $value['items'] ?? null;
                },
            ];
        }
        catch (NoValidatationFoundException $e) {
            if(!isset($config['validate'])) {
                throw $e;
            }
        }
    }
}