<?php

namespace GraphQL\Type\Definition;
class UserErrorsInputObjectType extends UserErrorsType
{
    public const FIELDS_NAME = 'fields';

    protected function __construct(array $config, array $path, bool $isParentList = false)
    {
        $fields = $this->_addFields($config['type'], $config, $path);

        if (! empty($config['validate'])) {
            $this->_addFieldsErrorField($fields, $config, $path);
        }

        parent::__construct($config, $path, $isParentList);

        if ($isParentList) {
            $this->_addPathField($fields);
        }
    }


    protected function _addFields(Type $type, array $config, array $path): array
    {
        $fields = [];
        foreach ($type->getFields() as $key => $field) {
            $fieldConfig = $field->config;
            $newType = self::create([
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

    /**
     * Adds the 'fields' field to the final fields.
     */
    protected function _addFieldsErrorField(array &$fields, array $config, array $path): void
    {
        $fields[self::FIELDS_NAME] = [
            'type' => new ObjectType([
                'name' => $this->_nameFromPath(array_merge($path, ['suberrors'])),
                'description' => 'Validation errors for nested fields.',
                'fields' => $fields,
            ]),
            'description' => 'Validation errors for nested fields.',
            'resolve' => static function ($value) {
                return $value[self::FIELDS_NAME] ?? null;
            },
        ];
    }
}
