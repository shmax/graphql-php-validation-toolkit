<?php declare(strict_types=1);

namespace GraphQL\Type\Definition;

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
class UserErrorsType extends ObjectType
{
    protected const CODE_NAME = 'code';
    protected const MESSAGE_NAME = 'msg';
    public const FIELDS_NAME = 'suberrors';

    protected function __construct(array $config, array $path, bool $isParentList = false)
    {
        $fields = $config['fields'] ?? [];
        $this->_addErrorFields($config, $fields, $path);

        // Add the path field if this is part of a list type
        if ($isParentList) {
            $this->_addPathField($fields);
        }

        $pathEnd = end($path);
        assert($pathEnd != false);

        parent::__construct([
            'name' => $this->_nameFromPath($path) . 'Error',
            'description' => 'User errors for ' . \ucfirst((string)$pathEnd),
            'fields' => $fields,
        ]);
    }

    /**
     * Factory method to create the appropriate type (InputObjectType, ListOfType, NonNull, or scalar).
     */
    public static function create(array $config, array $path, bool $isParentList = false): ?self
    {
        $type = $config['type'];
        $resolvedType = self::_resolveType($type);

        // Handle InputObjectType
        if ($resolvedType instanceof InputObjectType) {
            return new UserErrorsInputObjectType($config, $path, $isParentList);
        }

        // Handle ListOfType
        if ($resolvedType instanceof ListOfType) {
            return UserErrorsListOfType::createInstance($config, $path);
        }

        // Handle NonNull
        if ($resolvedType instanceof NonNull) {
            return UserErrorsNonNullType::createInstance($config, $path);
        }

        // Handle Scalar types (e.g., bool, int, string)
        if (self::isScalarType($resolvedType)) {
            return new self($config, $path, $isParentList); // Scalar types can use the base class directly
        }

        return null;
    }

    protected function _addPathField(array &$finalFields): void
    {
        if (! empty($finalFields['code']) /* || ! empty($finalFields['suberrors']) */) {
            $finalFields['path'] = [
                'type' => Type::listOf(Type::int()),
                'description' => 'A path describing this item\'s location in the nested array',
                'resolve' => static function ($value) {
                    return $value['path'];
                },
            ];
        }
    }

    protected static function isScalarType(Type $type): bool
    {
        return $type === Type::boolean()
            || $type === Type::int()
            || $type === Type::string()
            || $type instanceof ScalarType;
    }

    protected static function _resolveType($type): Type
    {
        return is_callable($type) ? $type() : $type;
    }

    protected function _addErrorFields(array $config, array &$fields, array $path): void
    {
        if (isset($config['validate'])) {
            $fields[static::CODE_NAME] = [
                'type' => Type::int(),
                'description' => 'A numeric error code. 0 on success, non-zero on failure.',
            ];

            $fields[static::MESSAGE_NAME] = [
                'type' => Type::string(),
                'description' => 'An error message.',
            ];
        }
    }

    protected function _nameFromPath(array $path): string
    {
        return implode('_', array_map('ucfirst', $path));
    }
}
