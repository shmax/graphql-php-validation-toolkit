<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;

class ListOfErrorType extends ErrorType
{
    public const ITEMS_NAME = 'items';

    protected const PATH_NAME = '__path';

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
                    static::PATH_NAME => [
                        'type' => Type::listOf(Type::int()),
                        'description' => 'A path describing this item\'s location in the nested array',
                        'resolve' => static function ($value) {
                            return $value[static::PATH_NAME];
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
        $this->_validateListOfType($arg, $value, $res);
    }

    /**
     * @param array<string, mixed> $config
     * @param mixed[] $value
     * @param array<mixed> $res
     * @param Array<string|int> $path
     */
    protected function _validateListOfType(array $config, array $value, array &$res, array $path = [0]): void
    {
        $validate = $config['items']['validate'] ?? null;
        $wrappedType = $config['type']->getWrappedType();
        foreach ($value as $idx => $subValue) {
            $path[\count($path) - 1] = $idx;
            if ($wrappedType instanceof ListOfErrorType) {
                $newPath = $path;
                $newPath[] = 0;
                $this->_validateListOfType(["type" => $wrappedType, "validate" => $validate], $subValue, $res, $newPath);
            } else {
                $err = static::_formatValidationResult($validate ? $validate($subValue) : 0);
                if ($err) {
                    $err[static::PATH_NAME] = $path;
                    $res[] = $err;
                }
            }
        }
    }
}