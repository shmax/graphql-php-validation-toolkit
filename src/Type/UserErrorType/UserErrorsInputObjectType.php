<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;

class UserErrorsInputObjectType extends UserErrorsType
{
    public const FIELDS_NAME = 'fieldErrors';

    protected function __construct(array $config, array $path)
    {
        parent::__construct($config, $path);

        try {
            $errorFields = $this->getErrorFields($config['type'], $path);
            if (!empty($errorFields)) {
                $this->config['fields'] ??= [];
                $this->config['fields'][self::FIELDS_NAME] = [
                    'type' => UserErrorsType::_set(new ObjectType([
                        'name' => $this->_nameFromPath(array_merge($path, [self::FIELDS_NAME])),
                        'description' => 'Validation errors for ' . \ucfirst((string)$path[\count($path) - 1]),
                        'fields' => $errorFields,
                    ]), $config),
                    'description' => 'Validation errors for ' . \ucfirst((string)$path[\count($path) - 1]),
                    'resolve' => static function ($value) {
                        return $value[self::FIELDS_NAME] ?? null;
                    },
                ];
            }
        } catch (NoValidatationFoundException $e) {
            if (empty($config['validate'])) {
                throw new NoValidatationFoundException($e);
            }
        }
    }

    protected function _validate(array $arg, mixed $value, array &$res): void
    {
        parent::_validate($arg, $value, $res);
        /**
         * @phpstan-var InputObjectType
         */
        $type = $arg['type'];
        $this->_validateInputObjectFields($type, $arg, $value, $res);
    }

    /**
     * @phpstan-param InputObjectType $type
     * @phpstan-param  ValidatedFieldConfig $objectConfig
     * @param array<mixed> $res
     */
    protected function _validateInputObjectFields(InputObjectType $type, array $objectConfig, mixed $value, array &$res): void
    {
        $fields = $type->getFields();
        foreach ($fields as $key => $field) {
            $error = null;
            $config = $field->config;

            $isKeyPresent = array_key_exists($key, $value);
            $isRequired = $config['required'] ?? false;
            if(is_callable($isRequired)) {
                $isRequired = $isRequired();
            }
            if($isRequired && !isset($value[$key])) {
                if ($isRequired === true) {
                    $error = ['error' => [1, "$key is required"]];
                }
                else if (is_array($isRequired)) {
                    $error = ['error' => $isRequired];
                }
            }
            else if ($isKeyPresent) {
                $error = $config['validate']($value[$key] ?? null);
            }

            $res[static::FIELDS_NAME][$key] = $error;
        }
    }



    protected function getErrorFields(Type $type, array $path): array
    {
        $fields = [];
        foreach ($type->getFields() as $key => $field) {
            $fieldConfig = $field->config;
            try {
                $newType = self::create([
                    'type' => $field->getType(),
                    'errorCodes' => $fieldConfig['errorCodes'] ?? null,
                    'validate' => $fieldConfig['validate'] ?? null,
                    'typeSetter' => $this->config['typeSetter'] ?? null,
                ], array_merge($path, [$key]));
            } catch (NoValidatationFoundException $e) {
                // continue. we'll finish building all fields, and throw our own error at the end if we don't wind up with anything.
                continue;
            }

            if ($newType) {
                $fields[$key] = [
                    'description' => 'Error for ' . $key,
                    'type' => $newType,
                ];
            }
        }

        if(empty($fields) && !isset($this->config['validate'])) {
            throw new NoValidatationFoundException();
        }

        return $fields;
    }
}
