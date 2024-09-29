<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;

class UserErrorsListOfType extends UserErrorsType
{
    protected function __construct(array $config, array $path)
    {
        parent::__construct($config, $path);
        $fields = [];
        $this->_addItemsErrorField($fields, $config, $path);
    }

    protected function _addItemsErrorField(array &$fields, array $config, array $path): void
    {
        $type = $this->_resolveType($config['type'], true);
        try {

            $errorType = static::create([
                'type' => $type,
                'validate' => $type->config['validate'] ?? null
            ], [$this->name, $type->name]);

            // You can nest ListOf as deeply as you like, so we provide the 'path' field so the user can map any errors back to the original input value
            $errorType->config['fields']['path'] = [
                'type' => Type::listOf(Type::int()),
                'description' => 'A path describing this item\'s location in the nested array',
                'resolve' => static function ($value) {
                    return $value['path'];
                },
            ];;

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