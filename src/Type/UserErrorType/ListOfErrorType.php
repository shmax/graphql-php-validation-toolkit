<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;

class ListOfErrorType extends ErrorType
{
    public const ITEMS_NAME = 'items';

    protected const PATH_NAME = '_path';

    protected function __construct(array $config, array $path)
    {
        parent::__construct($config, $path);
        assert($config['type'] instanceof ListOfType);
        $type = $config['type']->getInnermostType();
        try {
            if (static::isScalarType($type)) {
                $validate = $config[static::ITEMS_NAME]['validate'] ?? null;
                $errorCodes = $config[static::ITEMS_NAME]['errorCodes'] ?? null;
            } else {
                if (isset($config[static::ITEMS_NAME])) {
                    throw new \Exception("'items' is only supported for scalar types");
                }

                $validate = $type->config['validate'] ?? null;
                $errorCodes = $type->config['errorCodes'] ?? null;
            }

            $errorType = static::create([
                'type' => $type,
                'typeSetter' => $config['typeSetter'] ?? null,
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

            $this->config['fields']['_' . static::ITEMS_NAME] = [
                'type' => Type::listOf($errorType),
                'description' => 'Validation errors for each ' . $type->name() . ' in the list',
                'resolve' => static function ($value) {
                    return $value[static::ITEMS_NAME] ?? [];
                },
            ];
        } catch (NoValidatationFoundException $e) {
            if (empty($config['required']) && !isset($config['validate']) && !isset($config[static::ITEMS_NAME]['validate'])) {
                throw $e;
            }
        }
    }

    static protected function empty(mixed $value): bool
    {
        return parent::empty($value) || count($value) === 0;
    }

    protected function _validate(array $arg, mixed $value, array &$res): void
    {
        $this->_validateListOfType($arg, $value, $res, [0]);
    }

    /**
     * @param array<string, mixed> $config
     * @param mixed[] $value
     * @param array<mixed> $res
     * @param Array<string|int> $path
     */
    protected function _validateListOfType(array $config, array $value, array &$res, array $path): void
    {
        $validate = $this->config[static::ITEMS_NAME]['validate'] ?? null;
        $wrappedType = $config['type']->getWrappedType();
        $wrappedErrorType = $this->config['fields']['_' . static::ITEMS_NAME]['type']->getWrappedType();

        foreach ($value as $idx => $subValue) {
            // Update the path with the current index
            $path[\count($path) - 1] = $idx;

            // If the wrapped type is a list, recursively validate each item
            if ($wrappedType instanceof ListOfType) {
                $newPath = [...$path, 0]; // Append 0 for list path
                $this->_validateListOfType(['type' => $wrappedType, 'validate' => $validate], $subValue, $res, $newPath);
                continue; // Skip to the next iteration
            }

            // Validate scalar or complex types
            if ($wrappedType instanceof ScalarType) {
                $err = static::_formatValidationResult($validate($subValue));
            } else {
                $err = $wrappedErrorType->validate(['type' => $wrappedType], $subValue);
            }

            // Check for errors and add to results if necessary
            if ($err) {
                $diff = array_diff_key($err, array_flip([static::CODE_NAME, static::MESSAGE_NAME]));

                if (!empty($diff) || ($err[static::CODE_NAME] ?? 0) !== 0) {
                    $err[static::PATH_NAME] = $path;
                    $res[static::ITEMS_NAME][] = $err;
                }
            }
        }
    }
}