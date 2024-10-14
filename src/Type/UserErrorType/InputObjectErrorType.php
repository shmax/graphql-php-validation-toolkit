<?php

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;

class InputObjectErrorType extends ErrorType
{
    protected function __construct(array $config, array $path)
    {
        parent::__construct($config, $path);

        try {
            $errorFields = $this->getErrorFields($config['type'], $path);
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
//        $fieldsB = $this->config['fields']['fieldErrors'];
        foreach ($fields as $key => $field) {
            $config = $field->config;

//            $configB = $this->config['type']->getFields();

            $isRequired = $config['required'] ?? false;
            $code = 0;
            $msg = '';

            if (is_callable($isRequired)) {
                $isRequired = $isRequired();
            }

            if ($isRequired && !array_key_exists($key, $value)) {
                // Handle required field logic
                if (is_array($isRequired)) {
                    [$code, $msg] = $isRequired + [1, "$key is required"];
                } else {
                    $code = 1;
                    $msg = "$key is required";
                }
            } elseif (array_key_exists($key, $value)) {
                // Handle validation logic for present keys
                $validate = $config['validate'] ?? null;
                $validationResult = is_callable($validate) ? $validate($value[$key]) : [0, ''];
                if (is_array($validationResult)) {
                    [$code, $msg] = $validationResult + [0, ''];
                } else {
                    $code = $validationResult;
                }
            }

            if ($code !== 0) {
                // Populate result array
                $res[$key][static::CODE_NAME] = $code;
                $res[$key][static::MESSAGE_NAME] = $msg;
            }
        }
    }

    protected function getErrorFields(Type $type, array $path): array
    {
        $args = [];
        foreach ($type->getFields() as $key => $field) {
            $fieldConfig = $field->config;
            try {
//                $newType = self::create(array_merge([
//                    'type' => $field->getType(),
//                    'errorCodes' => $fieldConfig['errorCodes'] ?? null,
//                    'validate' => $fieldConfig['validate'] ?? null,
//                    'typeSetter' => $this->config['typeSetter'] ?? null,
//                ], array_merge($path, [$key]));


                $newType = self::create(array_merge($fieldConfig, ['type' => $field->getType()]), array_merge($path, [$key]));
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
