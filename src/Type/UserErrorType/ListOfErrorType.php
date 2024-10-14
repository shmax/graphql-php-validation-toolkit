<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;

class ListOfErrorType extends ErrorType
{
    public const ITEMS_NAME = 'items';

    protected function __construct(array $config, array $path)
    {
        parent::__construct($config, $path);
        $type = $config['type']->getInnermostType();
        try {
            if (static::isScalarType($type)) {
                $validate = $config[static::ITEMS_NAME]['validate'] ?? null;
                $errorCodes = $config[static::ITEMS_NAME]['errorCodes'] ?? null;
            } else {
                if (isset($config['item'])) {
                    throw new \Exception("'item' is only supported for scalar types");
                }

                $validate = $type->config['validate'] ?? null;
                $errorCodes = $type->config['errorCodes'] ?? null;
            }

            $errorType = static::create([
                'type' => $type,
                'validate' => $validate,
                'errorCodes' => $errorCodes,
                'fields' => [
                    'path' => [
                        'type' => Type::listOf(Type::int()),
                        'description' => 'A path describing this item\'s location in the nested array',
                        'resolve' => static function ($value) {
                            return $value['path'];
                        },
                    ]
                ],
            ], [$this->name, $type->name]);

            $this->config['fields'][static::ITEMS_NAME] = [
                'type' => Type::listOf($errorType),
                'description' => 'Validation errors for each ' . $type->name() . ' in the list',
                'resolve' => static function ($value) {
                    return $value[static::ITEMS_NAME] ?? [];
                },
            ];
        } catch (NoValidatationFoundException $e) {
            if (!isset($config['validate'])) {
                throw $e;
            }
        }
    }

    protected function _validate(array $arg, mixed $value, array &$res): void
    {

    }
}