<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\PhpEnumType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;

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
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 */
abstract class ErrorType extends ObjectType
{
    protected const CODE_NAME = '_code';
    protected const MESSAGE_NAME = '_msg';

    /**
     * @param ValidatedFieldConfig $arg
     * @param mixed $value
     * @param array<mixed> $res ;
     */

    abstract protected function _validate(array $arg, mixed $value, array &$res): void;


    /**
     * @phpstan-param UserErrorsConfig $config
     * @phpstan-param Path $path
     */
    protected function __construct(array $config, array $path)
    {
        $fields = $config['fields'] ?? [];
        $this->_addCodeAndMessageFields($config, $fields, $path);

        $pathEnd = end($path);
        assert($pathEnd != false);

        parent::__construct(array_merge($config, [
            'name' => $this->_nameFromPath($path) . 'Error',
            'description' => 'User errors for ' . \ucfirst((string)$pathEnd),
            'fields' => $fields,
            'typeSetter' => $config['typeSetter'] ?? null,
        ]));
    }

    /**
     * Factory method to create the appropriate type (InputObjectType, ListOfType, NonNull, or scalar).
     *
     * @phpstan-param UserErrorsConfig $config
     * @phpstan-param Path $path
     */
    public static function create(array $config, array $path): self
    {
        $resolvedType = self::_resolveType($config['type']);

        if ($resolvedType instanceof InputObjectType) {
            $type = new InputObjectErrorType($config, $path);
        } else if ($resolvedType instanceof ListOfType) {
            $type = new ListOfErrorType($config, $path);
        } else if ($resolvedType instanceof NonNull) {
            $config['type'] = static::_resolveType($config['type'], true);
            $type = static::create($config, $path);
        } else if ($resolvedType instanceof StringType) {
            $type = new StringErrorType($config, $path);
        } else if ($resolvedType instanceof IDType) {
            $type = new IDErrorType($config, $path);
        } else if ($resolvedType instanceof ScalarType) {
            $type = new ScalarErrorType($config, $path);
        } else if ($resolvedType instanceof EnumType) {
            $type = new EnumErrorType($config, $path);
        } else {
            throw new \Exception("Unknown type");
        }
        return static::_set($type, $config);
    }

    static protected function empty(mixed $value): bool
    {
        return !isset($value);
    }


    /**
     * @param ValidatedFieldConfig $config
     * @param mixed $value
     *
     * @return mixed[]
     */
    public function validate(array $config, $value): array
    {
        $res = [];
        if (is_callable($config['validate'] ?? null)) {
            $result = static::_formatValidationResult($config['validate']($value));

            if (isset($result) && $result[static::CODE_NAME] !== 0) {
                $res = $result;
            }
        }

        if (\is_callable($config['type'])) {
            $config['type'] = $config['type']();
        }

        $this->_validate($config, $value, $res);
        return $res;
    }

    /**
     * @param bool|array{int|\UnitEnum, string} $requiredValue
     * @return bool
     */
    static function isRequired($requiredValue): bool
    {
        if (is_callable($requiredValue)) {
            $requiredValue = $requiredValue();
        }

        if (is_bool($requiredValue)) {
            return $requiredValue;
        }

        return $requiredValue[0] !== 0;
    }

    /**
     * @param int|array{0: int|\UnitEnum, 1: string} $result
     * @return array{0: int|\UnitEnum, 1: string}
     * @throws \Exception
     */
    protected static function _formatValidationResult(mixed $result): ?array
    {
        if (is_array($result) && count($result) === 2) {
            [$code, $msg] = $result;
        } elseif (is_int($result) || $result instanceof \UnitEnum) {
            $code = $result;
            $msg = ''; // Set a default message or leave as null
        } else {
            throw new \Exception("Invalid response from the validate callback");
        }

        if ($code === 0) {
            return null;
        }

        return [static::CODE_NAME => $code, static::MESSAGE_NAME => $msg];
    }

    protected static function isScalarType(Type $type): bool
    {
        return $type instanceof ScalarType;
    }

    static protected function _resolveType(Type|callable $type, bool $resolveWrapped = false): Type
    {
        if (\is_callable($type)) {
            $type = $type();
        }

        if ($resolveWrapped && $type instanceof WrappingType) {
            $type = $type->getWrappedType();
        }

        return $type;
    }

    /**
     * @template T of Type
     * @param T $type
     * @param UserErrorsConfig $config
     * @return T
     */
    static protected function _set(Type $type, array $config): Type
    {
        if (\is_callable($config['typeSetter'] ?? null)) {
            return $config['typeSetter']($type);
        }

        return $type;
    }

    /**
     * @param UserErrorsConfig $config
     * @param array<FieldDefinitionConfig> $fields
     * @param Path $path
     * @throws \Exception
     */
    protected function _addCodeAndMessageFields(array $config, array &$fields, array $path): void
    {
        if (isset($config['validate']) || !empty($config['required'])) {
            if (isset($config['errorCodes'])) {
                // error code. By default, this is an int, but if the user supplies the optional `errorCodes`
                // enum property, then it takes that type

                if (!isset($config['validate']) && empty($config['required'])) {
                    throw new \Exception('If you specify errorCodes, you must also provide a \'validate\' callback, or mark the field as \'required\'');
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
                ];
            }

            $fields[static::CODE_NAME]['resolve'] = static function ($error) {
                return $error[static::CODE_NAME] ?? 0;
            };

            $fields[static::MESSAGE_NAME] = [
                'type' => Type::string(),
                'description' => 'An error message.',
                'resolve' => static function ($error) {
                    return $error[static::MESSAGE_NAME] ?? '';
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

    /**
     * @param Path $path
     */
    protected function _nameFromPath(array $path): string
    {
        return implode('_', array_map('ucfirst', $path));
    }
}
