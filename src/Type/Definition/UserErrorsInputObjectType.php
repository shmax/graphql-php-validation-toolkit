<?php

namespace GraphQL\Type\Definition;

class UserErrorsInputObjectType extends UserErrorsType
{
    public const FIELDS_NAME = 'fields';

    protected function __construct(array $config, array $path, bool $isParentList = false)
    {
        parent::__construct($config, $path, $isParentList);

        $errorFields = $this->getErrorFields($config['type'], $config, $path);
        if(!empty($errorFields)) {
            $this->config['fields'] ??= [];
            $this->config['fields'][self::FIELDS_NAME] = [
                'type' => UserErrorsType::_set(new ObjectType([
                    'name' => $this->_nameFromPath(array_merge($path, ['fieldErrors'])),
                    'description' => 'Validation errors for ' . \ucfirst((string)$path[\count($path) - 1]),
                    'fields' => $errorFields,
                ]), $config),
                'description' => 'Validation errors for nested fields.',
                'resolve' => static function ($value) {
                    return $value[self::FIELDS_NAME] ?? null;
                },
            ];
        }
        else if (empty($config['validate'])) {
            throw new NoValidatationFoundException();
        }
    }


    protected function getErrorFields(Type $type, array $config, array $path): array
    {
        $fields = [];
        foreach ($type->getFields() as $key => $field) {
            $fieldConfig = $field->config;
            $newType = self::_create([
                'type' => $field->getType(),
                'validate' => $fieldConfig['validate'] ?? null,
            ], array_merge($path, [$key]));

            if ($newType) {
                $fields[$key] = [
                    'description' => 'Error for ' . $key,
                    'type' => $newType,
                ];
            }
        }

        return $fields;
    }
}
