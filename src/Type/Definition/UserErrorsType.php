<?php declare(strict_types=1);

namespace GraphQL\Type\Definition;

use GraphQL\Type\Definition\Type;

/**
 * @phpstan-type UserErrorsConfig array{
 *   type: Type,
 *   errorCodes?: class-string<\UnitEnum>|null,
 *   fields?: array<string,mixed>,
 *   validate?: null|callable(mixed $value): mixed,
 *   isRoot?: bool,
 *   typeSetter?: callable|null,
 * }
 * @phpstan-type Path array<string|int>
 * @phpstan-import-type ObjectConfig from ObjectType
 * @phpstan-import-type ValidatedFieldConfig from ValidatedFieldDefinition
 * @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition
 */
final class UserErrorsType extends ObjectType
{
    public const SUBERRORS_NAME = 'suberrors';
    protected const CODE_NAME = 'code';
    protected const MESSAGE_NAME = 'msg';

    /**
     * @phpstan-param UserErrorsConfig $config
     * @phpstan-param Path $path
     */
    public function __construct(array $config, array $path, bool $isParentList = false)
    {
        $finalFields = $config['fields'] ?? [];
        $this->_addErrorCodes($config, $finalFields, $path);

        $type = $this->_resolveType($config['type']);
        if ($type instanceof InputObjectType) {
            $this->_buildInputObjectType($type, $config, $path, $finalFields, $isParentList);
        }

        if ($isParentList) {
            $this->_addPathField($finalFields);
        }

        $pathEnd = end($path);
        assert($pathEnd != false);
        parent::__construct([
            'name' => $this->_nameFromPath(\array_merge($path)) . \ucfirst('error'),
            'description' => 'User errors for ' . \ucfirst((string)$pathEnd),
            'fields' => $finalFields,
            'type' => $config['type']
        ]);
    }

    /**
     * @param Type|callable():Type $type
     */
    protected function _resolveType(mixed $type): Type
    {
        if (\is_callable($type)) {
            $type = $type();
        }

        if ($type instanceof WrappingType) {
            $type = $type->getInnermostType();
        }

        return $type;
    }

    /**
     * @phpstan-param UserErrorsConfig $config
     */
    public static function needSuberrors(array $config, bool $isParentList): bool
    {
        return ! empty($config['validate']) || ! empty($config['isRoot']) || $isParentList;
    }

    /**
     * @phpstan-param UserErrorsConfig $config
     * @phpstan-param Path $path
     * @return array<string,mixed>
     */
    protected function _buildInputObjectFields(InputObjectType $type, array $config, array $path): array
    {
        $fields = [];
        foreach ($type->getFields() as $key => $field) {
            /** @phpstan-var ValidatedFieldConfig */
            $fieldConfig = $field->config;
            $fieldType = $this->_resolveType($field->config['type']);
            $newType = self::create(
                [
                    'validate' => $fieldConfig['validate'] ?? null,
                    'errorCodes' => $fieldConfig['errorCodes'] ?? null,
                    'type' => $fieldType,
                    'fields' => [],
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

    /**
     * @param UserErrorsConfig $config
     * @param Path $path
     * @param array<mixed> $finalFields
     * @param bool $isParentList
     * @return void
     */
    protected function _buildInputObjectType(InputObjectType $type, array $config, array $path, array &$finalFields, bool $isParentList)
    {
        $createSubErrors = UserErrorsType::needSuberrors($config, $isParentList);
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
                'description' => 'Validation errors for ' . \ucfirst((string)$path[\count($path) - 1]),
                'resolve' => static function (array $value) {
                    return $value[static::SUBERRORS_NAME] ?? null;
                },
            ];
        } else {
            $finalFields += $fields;
        }
    }

    /**
     * @phpstan-param UserErrorsConfig $config
     * @phpstan-param array<mixed> $finalFields
     * @phpstan-param Path $path
     */
    protected function _addErrorCodes($config, &$finalFields, array $path): void
    {
        if (isset($config['errorCodes'])) {
            if (! isset($config['validate'])) {
                throw new \Exception('If you specify errorCodes, you must also provide a validate callback');
            }

            $type = new PhpEnumType($config['errorCodes'], $this->_nameFromPath(\array_merge($path)) . 'ErrorCode');
            $type->description = "Error code";

            /** code property */
            $finalFields[static::CODE_NAME] = [
                'type' => $this->_set($type, $config),
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

    /**
     * @phpstan-param array<mixed> $finalFields
     */
    protected function _addPathField(array &$finalFields): void
    {
        if (! empty($finalFields['code']) || ! empty($finalFields['suberrors'])) {
            $finalFields['path'] = [
                'type' => Type::listOf(Type::int()),
                'description' => 'A path describing this item\'s location in the nested array',
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
     * @param UserErrorsConfig $config
     * @phpstan-param Path $path
     *
     * @return static|null
     */
    public static function create(array $config, array $path, bool $isParentList = false, string $name = ''): ?self
    {
        if (\is_callable($config['validate'] ?? null)) {
            $config['fields'][static::CODE_NAME] = $config['fields'][static::CODE_NAME] ?? static::_generateIntCodeType();
            $config['fields'][static::MESSAGE_NAME] = $config['fields'][static::MESSAGE_NAME] ?? static::_generateMessageType();
        }

        $userErrorType = new UserErrorsType($config, $path, $isParentList);
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

    /**
     * @return UnnamedFieldDefinitionConfig
     */
    protected static function _generateIntCodeType(): array
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

                return $error[0] ?? null;
            },
        ];
    }

    /**
     * @return UnnamedFieldDefinitionConfig
     */
    protected static function _generateMessageType(): array
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

                return $error[1] ?? null;
            },
        ];
    }

    /**
     * @param Path $path
     */
    protected function _nameFromPath(array $path): string
    {
        return \implode('_', \array_map(static fn ($node) => ucfirst((string)$node), $path));
    }
}
