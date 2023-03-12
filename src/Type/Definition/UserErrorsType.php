<?php declare(strict_types=1);

namespace GraphQL\Type\Definition;

/**
 * @phpstan-import-type InputObjectValidatedFieldConfig from ValidatedFieldDefinition
 */
final class UserErrorsType extends ObjectType
{
    public const SUBERRORS_NAME = 'suberrors';
    protected const CODE_NAME = 'code';
    protected const MESSAGE_NAME = 'msg';

    /**
     * @param mixed[]  $config
     * @param string[] $path
     */
    public function __construct(array $config, array $path, bool $isParentList = false)
    {
        $finalFields = $config['fields'] ?? [];

        if (! isset($config['type'])) {
            throw new \Exception('You must specify a type for your field');
        }

        $this->_addErrorCodes($config, $finalFields, $path);

        $type = $this->_getType($config);
        if ($type instanceof InputObjectType) {
            $this->_buildInputObjectType($type, $config, $path, $finalFields, $isParentList);
        }

        if ($isParentList) {
            $this->_addPathField($finalFields);
        }

        parent::__construct([
            'name' => $this->_nameFromPath(\array_merge($path)) . \ucfirst('error'),
            'description' => 'User errors for ' . \ucfirst($path[\count($path) - 1]),
            'fields' => $finalFields,
        ]);
    }

    protected function _getType($config)
    {
        $type = $config['type'];
        if (\is_callable($type)) {
            $type = $type();
        }

        if ($type instanceof WrappingType) {
            $type = $type->getInnermostType();
        }

        return $type;
    }

    public static function needSuberrors(array $config, bool $isParentList): bool
    {
        return ! empty($config['validate']) || ! empty($config['isRoot']) || $isParentList;
    }

    protected function _buildInputObjectFields(InputObjectType $type, $config, $path)
    {
        $fields = [];
        foreach ($type->getFields() as $key => $field) {

            /** @phpstan-var InputObjectValidatedFieldConfig */
            $fieldConfig = $field->config;
            $fieldType = $this->_getType($field->config);
            $newType = static::create(
                [
                    'validate' => $fieldConfig['validate'] ?? null,
                    'errorCodes' => $fieldConfig['errorCodes'] ?? null,
                    'type' => $fieldType,
                    'typeSetter' => $config['typeSetter'] ?? null,
                ],
                \array_merge($path, [$key]),
                $field->getType() instanceof ListOfType
            );
            if (! empty($newType)) {
                $fields[$key] = [
                    'description' => 'Error for ' . $key,
                    'type' => $field->getType() instanceof ListOfType ? Type::listOf($newType) : $newType,
                    'resolve' => static function ($value) use ($key) {
                        return $value[$key] ?? null;
                    },
                ];
            }
        }

        return $fields;
    }

    protected function _buildInputObjectType(InputObjectType $type, $config, $path, &$finalFields, $isParentList)
    {
        $createSubErrors = static::needSuberrors($config, $isParentList);

        $fields = $this->_buildInputObjectFields($type, $config, $path);

        if ($createSubErrors && \count($fields) > 0) {
            /**
             * suberrors property.
             */
            $finalFields[static::SUBERRORS_NAME] = [
                'type' => $this->_set(new ObjectType([
                    'name' => $this->_nameFromPath(\array_merge($path, ['fieldErrors'])),
                    'description' => 'User Error',
                    'fields' => $fields,
                ]), $config),
                'description' => 'Validation errors for ' . \ucfirst($path[\count($path) - 1]),
                'resolve' => static function (array $value) {
                    return $value[static::SUBERRORS_NAME] ?? null;
                },
            ];
        } else {
            $finalFields += $fields;
        }
    }

    protected function _addErrorCodes($config, &$finalFields, $path)
    {
        if (isset($config['errorCodes'])) {
            if (! isset($config['validate'])) {
                throw new \Exception('If you specify errorCodes, you must also provide a validate callback');
            }

            /** code property */
            $finalFields[static::CODE_NAME] = [
                'type' => $this->_set(new EnumType([
                    'name' => $this->_nameFromPath(\array_merge($path)) . 'ErrorCode',
                    'description' => 'Error code',
                    'values' => $config['errorCodes'],
                ]), $config),
                'description' => 'An error code',
                'resolve' => static function ($value) {
                    return $value['error'][0] ?? null;
                },
            ];

            /**
             * msg property.
             */
            $finalFields[static::MESSAGE_NAME] = [
                'type' => Type::string(),
                'description' => 'A natural language description of the issue',
                'resolve' => static function ($value) {
                    return $value['error'][1] ?? null;
                },
            ];
        }
    }

    protected function _addPathField(&$finalFields)
    {
        if (! empty($finalFields['code']) || ! empty($finalFields['suberrors'])) {
            $finalFields['path'] = [
                'type' => Type::listOf(Type::int()),
                'description' => 'A path describing this items\'s location in the nested array',
                'resolve' => static function ($value) {
                    return $value['path'];
                },
            ];
        }
    }

    /**
     * @param mixed[] $config
     *
     * @return Type
     */
    protected function _set(Type $type, array $config)
    {
        if (\is_callable($config['typeSetter'] ?? null)) {
            $config['typeSetter']($type);
        }

        return $type;
    }

    /**
     * @param mixed[]  $config
     * @param string[] $path
     *
     * @return static|null
     */
    public static function create(array $config, array $path, bool $isParentList = false, string $name = ''): ?self
    {
        $config['fields'] = $config['fields'] ?? [];
        if (\is_callable($config['validate'] ?? null)) {
            $config['fields'][static::CODE_NAME] = $config['fields'][static::CODE_NAME] ?? static::_generateIntCodeType();
            $config['fields'][static::MESSAGE_NAME] = $config['fields'][static::MESSAGE_NAME] ?? static::_generateMessageType();
        }

        $userErrorType = new static($config, $path, $isParentList);
        if (count($userErrorType->getFields()) > 0) {
            $userErrorType->name = ! empty($name) ? $name : $userErrorType->name;
            if (\is_callable($config['typeSetter'] ?? null)) {
                $config['typeSetter']($userErrorType);
            }

            return $userErrorType;
        }

        if (\count($path) == 1) {
            throw new \Exception("You must specify at least one 'validate' callback somewhere");
        }

        return null;
    }

    protected static function _generateIntCodeType()
    {
        return [
            'type' => Type::int(),
            'description' => 'A numeric error code. 0 on success, non-zero on failure.',
            'resolve' => static function ($value) {
                $error = $value['error'] ?? null;
                switch (\gettype($error)) {
                    case 'integer':
                        return $error;
                }

                return $error[0];
            },
        ];
    }

    protected static function _generateMessageType()
    {
        return [
            'type' => Type::string(),
            'description' => 'An error message.',
            'resolve' => static function ($value) {
                $error = $value['error'] ?? null;
                switch (\gettype($error)) {
                    case 'integer':
                        return '';
                }

                return $error[1];
            },
        ];
    }

    /**
     * @param string[] $path
     */
    protected function _nameFromPath(array $path): string
    {
        return \implode('_', \array_map('ucfirst', $path));
    }
}
