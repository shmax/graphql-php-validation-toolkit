<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\PhpEnumType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;

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
abstract class ErrorType extends ObjectType
{
    protected const CODE_NAME = '__code';
    protected const MESSAGE_NAME = '__msg';

    abstract protected function _validate(array $arg, mixed $value, array &$res): void;

    protected function __construct(array $config, array $path)
    {
        $fields = $config['fields'] ?? [];
        $this->_addCodeAndMessageFields($config, $fields, $path);

        $pathEnd = end($path);
        assert($pathEnd != false);

        parent::__construct([
            'name' => $this->_nameFromPath($path) . 'Error',
            'description' => 'User errors for ' . \ucfirst((string)$pathEnd),
            'fields' => $fields,
            'typeSetter' => $config['typeSetter'] ?? null,
        ]);
    }

    /**
     * Factory method to create the appropriate type (InputObjectType, ListOfType, NonNull, or scalar).
     */
    public static function create(array $config, array $path): ?self
    {
        $resolvedType = self::_resolveType($config['type']);

        if ($resolvedType instanceof InputObjectType) {
            $type = new InputObjectErrorType($config, $path);
        } else if ($resolvedType instanceof ListOfType) {
            $type = new ListOfErrorType($config, $path);
        } else if ($resolvedType instanceof NonNull) {
            $config['type'] = static::_resolveType($config['type'], true);
            $type = static::create($config, $path);
        } else if ($resolvedType instanceof ScalarType) {
            $type = new ScalarErrorType($config, $path);
        } else {
            throw new \Exception("Unknown type");
        }
        if (isset($type)) {
            $type = static::_set($type, $config);
        }
        return $type;
    }


    /**
     * @param mixed[] $arg
     * @param mixed $value
     *
     * @return mixed[]
     */
    public function validate(array $arg, $value): array
    {
        $res = [];
        if (\is_callable($arg['type'])) {
            $arg['type'] = $arg['type']();
        }

        if (\is_callable($arg['validate'] ?? null)) {
            $result = $arg['validate']($value);

            if (is_array($result) && count($result) === 2) {
                [$code, $msg] = $result;
            } elseif (is_int($result) || $result instanceof \UnitEnum) {
                $code = $result;
                $msg = ''; // Set a default message or leave as null
            } else {
                throw new \Exception("Invalid response from the validate callback");
            }
            if ($code !== 0) {
                $res[0] = $code;
                $res[1] = $msg;
            }
        }

        $this->_validate($arg, $value, $res);
        return $res;
    }

    protected static function isScalarType(Type $type): bool
    {
        return $type instanceof ScalarType;
    }

    static protected function _resolveType(mixed $type, $resolveWrapped = false): Type
    {
        if (\is_callable($type)) {
            $type = $type();
        }

        if ($resolveWrapped && $type instanceof WrappingType) {
            $type = $type->getWrappedType();
        }

        return $type;
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

                if (!isset($config['validate'])) {
                    throw new \Exception('If you specify errorCodes, you must also provide a validate callback');
                }
                $type = new PhpEnumType($config['errorCodes']);
                if (!isset($config['typeSetter'])) {
                    $type->name = $this->_nameFromPath(\array_merge($path, [$type->name]));
                }

                $fields[static::CODE_NAME] = [
                    'type' => static::_set($type, $config),
                    'description' => 'An enumerated error code.',
                ];
            } else {
                $fields[static::CODE_NAME] = [
                    'type' => Type::int(),
                    'description' => 'A numeric error code. 0 on success, non-zero on failure.',
                    'resolve' => static function ($error) {
                        return $error[0] ?? 0;
                    },
                ];
            }

            $fields[static::MESSAGE_NAME] = [
                'type' => Type::string(),
                'description' => 'An error message.',
                'resolve' => static function ($error) {
                    return $error[1] ?? '';
                },
            ];
        } else {
            if (isset($config['errorCodes'])) {
                if (!isset($config['validate'])) {
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
