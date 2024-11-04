<?php

namespace GraphQlPhpValidationToolkit\Type\ErrorType;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;
use GraphQlPhpValidationToolkit\Exception\OverlySpecializedValidationErrorType;

/**
 * @phpstan-import-type ValidationErrorConfig from ValidationErrorType
 * @phpstan-import-type Path from ValidationErrorType
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 * @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition
 * @phpstan-import-type ValidatedFieldDefinitionConfig from ValidatedFieldDefinition
 * @phpstan-import-type ValidationSettings from ValidatedFieldDefinition
 */
class InputObjectValidationErrorType extends ValidationErrorType
{
    /**
     * @param ValidatedFieldDefinitionConfig $fieldConfig
     * @param Path $path
     * @throws NoValidatationFoundException
     * @throws OverlySpecializedValidationErrorType
     */
    protected function __construct(array $fieldConfig, array $path)
    {
        parent::__construct($fieldConfig, $path);

        $errorFields = $this->getErrorFields($fieldConfig, $path);
        $this->config['fields'] = array_merge($this->config['fields'], $errorFields);
    }

    /**
     * @param ValidatedFieldDefinitionConfig $field
     * @param mixed $value
     * @param ValidationSettings $settings
     *
     * @return array<mixed>
     */
    public function validate(array $field, mixed $value, array $settings): array
    {
        $res = [];
        if (is_callable($field['validate'] ?? null)) {
            $result = static::_formatValidationResult($field['validate']($value));

            if (isset($result) && $result[static::CODE_NAME] !== 0) {
                $res = $result;
            }
        }

        $field['type'] = static::_resolveType($field['type']);
        $type = Type::getNamedType(self::_resolveType($field['type']));
        assert($type instanceof InputObjectType);

        $fields = $type->getFields();
        foreach ($fields as $key => $subfield) {
            /**
             * @var ValidationErrorConfig
             */
            $subfieldConfig = $subfield->config;
            $fieldErrorType = $this->config['fields'][$key]['type'] ?? null;

            if ($fieldErrorType) {
                $diff = [];
                $validationResult = null;
                $isKeyPresent = array_key_exists($key, $value);
                $isRequired = $subfieldConfig['required'] ?? false;
                if (is_callable($isRequired)) {
                    $isRequired = $isRequired();
                }
                if ($isRequired && empty($value[$key]) && ($settings['validationMode'] == 'full' || $isKeyPresent)) {
                    if ($isRequired === true) {
                        $validationResult = static::_formatValidationResult([1, "$key is required"]);
                    } else if (is_array($isRequired)) {
                        $validationResult = static::_formatValidationResult($isRequired);
                    }
                } else if ($isKeyPresent) {
                    $validate = $subfieldConfig['validate'] ?? null;
                    if ($fieldErrorType instanceof ListOfValidationErrorType || $fieldErrorType instanceof InputObjectValidationErrorType) {
                        $validationResult = $fieldErrorType->validate($subfieldConfig, $value[$key] ?? null, $settings);
                        $diff = array_diff_key($validationResult, array_flip([static::CODE_NAME, static::MESSAGE_NAME]));
                    } else if (isset($validate)) {
                        $validationResult = static::_formatValidationResult($validate($value[$key]));
                    }
                }

                if (!empty($validationResult) && (($validationResult[static::CODE_NAME] ?? null) !== 0 || !empty($diff))) {
                    $res[$key] = $validationResult;
                }
            }
        }

        return $res;
    }

    /**
     * @param ValidatedFieldDefinitionConfig $fieldConfig
     * @param Path $path
     * @return array<string, UnnamedFieldDefinitionConfig>
     * @throws NoValidatationFoundException
     * @throws OverlySpecializedValidationErrorType
     */
    protected function getErrorFields(array $fieldConfig, array $path): array
    {
        $type = $fieldConfig['type'];
        assert($type instanceof InputObjectType);
        $fields = [];
        foreach ($type->getFields() as $key => $subfield) {
            $subfieldConfig = $subfield->config;
            try {
                $newType = self::create(array_merge($subfieldConfig, ['type' => $subfield->getType()]), array_merge($path, [$key]));
            } catch (NoValidatationFoundException $e) {
                // continue. we'll finish building all fields, and throw our own error at the end if we don't wind up with anything.
                continue;
            }

            $fields[$key] = [
                'description' => 'Error for ' . $key,
                'type' => $newType,
            ];
        }

        if (empty($fields)) {
            if (!isset($this->config['validate'])) {
                throw new NoValidatationFoundException();
            }
            throw new OverlySpecializedValidationErrorType();
        }

        return $fields;
    }
}