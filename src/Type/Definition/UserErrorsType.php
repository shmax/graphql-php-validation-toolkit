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
        $this->_addCodeAndMessageFields($config, $fields, $path);

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
    protected static function _create(array $config, array $path, bool $isParentList = false): ?self
    {
        $type = null;
        $resolvedType = self::_resolveType($config['type']);

        // Handle InputObjectType
        if ($resolvedType instanceof InputObjectType) {
            try {
                $type = new UserErrorsInputObjectType($config, $path, $isParentList);
            }
            catch(NoValidatationFoundException $e) {
                $type = null;
            }
        }
        else if ($resolvedType instanceof ListOfType) {
            $type = new UserErrorsListOfType($config, $path, $isParentList);
        }

        else if ($resolvedType instanceof NonNull) {
            $type = new UserErrorsNonNullType($config, $path, $isParentList);
        } else if (self::isScalarType($resolvedType) && isset($config['validate'])) {
            $type = new self($config, $path, $isParentList); // Scalar types can use the base class directly
        }
        if(isset($type)) {
            $type = static::_set($type, $config);
        }

        return $type;
    }

    public static function create(array $config, array $path): self
    {
        $result = self::_create($config, $path);

        // If the root is null, no validation was found anywhere in the tree
        if ($result === null) {
            throw new NoValidatationFoundException();
        }

        return $result;
    }

    protected function _addPathField(array &$finalFields): void
    {
        if (! empty($finalFields['code']) || !empty($finalFields['suberrors'])) {
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

    static protected function _set(Type $type, array $config)
    {
        if (\is_callable($config['typeSetter'] ?? null)) {
            return $config['typeSetter']($type);
        }

        return $type;
    }

    protected function _addCodeAndMessageFields(array $config, array &$fields, array $path): void
    {
        if (isset($config['validate'])) {
            if (isset($config['errorCodes'])) {
                // error code. By default, this is an int, but if the user supplies the optional `errorCodes`
                // enum property, then it takes that type

                if (! isset($config['validate'])) {
                    throw new \Exception('If you specify errorCodes, you must also provide a validate callback');
                }
                $type = new PhpEnumType($config['errorCodes']);
                if(!isset($config['typeSetter'])) {
                    $type->name = $this->_nameFromPath(\array_merge($path)) . 'ErrorCode';
                }
                else {
                    $type->name = $type->name . 'ErrorCode';
                }
                $fields[static::CODE_NAME] = [
                    'type' => static::_set($type, $config),
                    'description' => 'An enumerated error code.',
                ];
            }
            else {
                $fields[static::CODE_NAME] = [
                    'type' => Type::int(),
                    'description' => 'A numeric error code. 0 on success, non-zero on failure.',
                ];
            }

            $fields[static::MESSAGE_NAME] = [
                'type' => Type::string(),
                'description' => 'An error message.',
            ];
        }
        else {
            if(isset($config['errorCodes'])) {
                if (! isset($config['validate'])) {
                    throw new \Exception('If you specify errorCodes, you must also provide a validate callback');
                }
            }

        }
    }

    protected function _nameFromPath(array $path): string
    {
        return implode('_', array_map('ucfirst', $path));
    }
}
