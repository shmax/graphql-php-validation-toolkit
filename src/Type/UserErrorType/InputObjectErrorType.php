<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;

/**
 * @phpstan-import-type UserErrorsConfig from ErrorType
 * @phpstan-import-type Path from ErrorType
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 * @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition
 */
class InputObjectErrorType extends ErrorType
{
    /**
     * @param UserErrorsConfig $config
     * @param Path $path
     * @throws NoValidatationFoundException
     */
    protected function __construct(array $config, array $path)
    {
        parent::__construct($config, $path);

        $errorFields = $this->getErrorFields($config, $path);
        $this->config['fields'] = array_merge($this->config['fields'], $errorFields);
    }

    protected function _validate(array $arg, mixed $value, array &$res): void
    {
        $type = Type::getNamedType($arg['type']);
        assert($type instanceof InputObjectType);

        $fields = $type->getFields();
        foreach ($fields as $key => $field) {
            $config = $field->config;
            $fieldErrorType = $this->config['fields'][$key]['type'] ?? null;

            if ($fieldErrorType) {
                $diff = [];
                $isKeyPresent = array_key_exists($key, $value);
                $isRequired = $config['required'] ?? false;
                if (is_callable($isRequired)) {
                    $isRequired = $isRequired();
                }
                if ($isRequired && $fieldErrorType::empty($value[$key])) {
                    if ($isRequired === true) {
                        $validationResult = ['error' => [1, "$key is required"]];
                    } else if (is_array($isRequired)) {
                        $validationResult = ['error' => $isRequired];
                    }
                } else if ($isKeyPresent) {
                    $validationResult = $fieldErrorType->validate($config, $value[$key] ?? null) + [static::CODE_NAME => 0, static::MESSAGE_NAME => ""];
                    $diff = array_diff_key($validationResult, array_flip([static::CODE_NAME, static::MESSAGE_NAME]));
                }

                if ($validationResult && ($validationResult[static::CODE_NAME] !== 0 || !empty($diff))) {
                    $res[$key] = $validationResult;
                }
            }
        }
    }

    /**
     * @param UserErrorsConfig $config
     * @param Path $path
     * @return array<string, UnnamedFieldDefinitionConfig>
     * @throws NoValidatationFoundException
     */
    protected function getErrorFields($config, array $path): array
    {
        $type = $config['type'];
        assert($type instanceof InputObjectType);
        $fields = [];
        foreach ($type->getFields() as $key => $field) {
            $fieldConfig = $field->config;
            try {
                $newType = self::create(array_merge($fieldConfig, ['type' => $field->getType(), 'typeSetter' => $config['typeSetter'] ?? null]), array_merge($path, [$key]));
            } catch (NoValidatationFoundException $e) {
                // continue. we'll finish building all fields, and throw our own error at the end if we don't wind up with anything.
                continue;
            }

            $fields[$key] = [
                'description' => 'Error for ' . $key,
                'type' => $newType,
            ];
        }

        if (empty($fields) && !isset($this->config['validate'])) {
            throw new NoValidatationFoundException();
        }

        return $fields;
    }
}
