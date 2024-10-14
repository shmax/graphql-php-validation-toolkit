<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;
use GraphQlPhpValidationToolkit\Tests\Utils;

class InputObjectErrorType extends ErrorType
{
    protected function __construct(array $config, array $path)
    {
        parent::__construct($config, $path);

        try {
            $errorFields = $this->getErrorFields($config, $path);
            $this->config['fields'] = array_merge($this->config['fields'], $errorFields ?? []);
        } catch (NoValidatationFoundException $e) {
            if (empty($config['validate'])) {
                throw new NoValidatationFoundException($e);
            }
        }
    }

    protected function _validate(array $arg, mixed $value, array &$res): void
    {
        /**
         * @phpstan-var InputObjectErrorType
         */
        $type = $arg['type'];

        $fields = $type->getFields();
        foreach ($fields as $key => $field) {
            $config = $field->config;
            $fieldErrorType = $this->config['fields'][$key]['type'];
            $isRequired = $config['required'] ?? false;
            $validationResult = [static::CODE_NAME => 0, static::MESSAGE_NAME => ''];

            if (is_callable($isRequired)) {
                $isRequired = $isRequired();
            }

            if ($isRequired && !array_key_exists($key, $value)) {
                // Handle required field logic
                if (is_array($isRequired)) {
                    $validationResult = static::_formatValidationResult($isRequired) + [
                            static::CODE_NAME => 1,
                            static::MESSAGE_NAME => "$key is required"
                        ];
                } else {
                    $validationResult = [static::CODE_NAME => 1, static::MESSAGE_NAME => "$key is required"];
                }
            } elseif (array_key_exists($key, $value)) {
                // Handle validation logic for present keys
                $validationResult = $fieldErrorType->validate($config, $value[$key]) + [static::CODE_NAME => 0, static::MESSAGE_NAME => ""];
            }

            if ($validationResult[static::CODE_NAME] !== 0) {
                // Populate result array
                $res[static::CODE_NAME] = 1; // not exposed for query, just needed so it doesn't get filtered higher-up in the tree
                $res[$key] = $validationResult;
            }
        }
    }

    protected function getErrorFields($config, array $path): array
    {
        $type = $config['type'];
        $args = [];
        foreach ($type->getFields() as $key => $field) {
            $fieldConfig = $field->config;
            try {
                $newType = self::create(array_merge($fieldConfig, ['type' => $field->getType(), 'typeSetter' => $config['typeSetter'] ?? null]), array_merge($path, [$key]));
            } catch (NoValidatationFoundException $e) {
                // continue. we'll finish building all fields, and throw our own error at the end if we don't wind up with anything.
                continue;
            }

            if ($newType) {
                $args[$key] = [
                    'description' => 'Error for ' . $key,
                    'type' => $newType,
                ];
            }
        }

        if (empty($args) && !isset($this->config['validate'])) {
            throw new NoValidatationFoundException();
        }

        return $args;
    }
}
