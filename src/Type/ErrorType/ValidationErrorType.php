<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Type\ErrorType;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\PhpEnumType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use GraphQlPhpValidationToolkit\Exception\NoValidatationFoundException;
use GraphQlPhpValidationToolkit\Exception\OverlySpecializedValidationErrorType;
use GraphQlPhpValidationToolkit\TypeRegistry;

/**
 * @phpstan-type ValidationErrorConfig array{
 *   name?: string,
 *   type?: Type,
 *   errorCodes?: class-string<\UnitEnum>|null,
 *   fields?: array<string,mixed>,
 *   validate?: null|callable(mixed $value): mixed
 * }
 * @phpstan-type Path array<string|int>
 * @phpstan-import-type ObjectConfig from ObjectType
 * @phpstan-import-type ValidatedFieldDefinitionConfig from ValidatedFieldDefinition
 * @phpstan-import-type ValidationSettings from ValidatedFieldDefinition
 * @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 */
class ValidationErrorType extends ObjectType
{
    protected const CODE_NAME = '_code';
    protected const MESSAGE_NAME = '_msg';

    /**
     * @var callable|null
     */
    protected static $_typeSetter = null;


    /**
     * @phpstan-param ValidationErrorConfig $config
     * @phpstan-param Path $path
     */
    public function __construct(array $config, array $path = [])
    {
        $fields = $config['fields'] ?? [];
        // _addCodeAndMessageFields now returns the modified fields array
        $fields = $this->_addCodeAndMessageFields($config, $fields, $path);

        $pathEnd = end($path);
//        assert($pathEnd != false);

        parent::__construct(array_merge($config, [
            'name' => $this->_generateName($path, $config),
            'description' => 'Validation error' . ($pathEnd ? ' for ' . ucfirst($pathEnd) : ''),
            'fields' => $fields,
        ]));
    }

    /**
     * @phpstan-param Path $path
     * @phpstan-param ValidationErrorConfig $config
     */
    protected function _generateName(array $path, array $config): string
    {
        if (isset($config['nameOverride'])) {
            return $config['nameOverride'];
        }
        $namespace = ($config['type'] ?? null) instanceof ScalarType ? null : $this->_nameFromPath($path);
        $leafName = $this->_leafName($config);
        // avoid loose comparison semantics by providing an explicit callback to array_filter
        $name = implode("_", array_filter([$namespace, $leafName], function ($v) {
            return $v !== null && $v !== '';
        }));
        return $name;
    }

    /**
     * @phpstan-param ValidationErrorConfig $config
     */
    protected function _leafName(array $config): string
    {
        $prefix = '';

        if (isset($config['errorCodes'])) {
            $phpEnum = (new PhpEnumType($config['errorCodes']))->name;
            $prefix .= preg_replace('~ErrorCode$~', '', $phpEnum);
        }

        if (isset($config['fields'][ListOfValidationErrorType::PATH_NAME])) {
            $prefix .= "ListItem";
        }

        return "{$prefix}ValidationError";
    }

    /**
     * Factory method to create the appropriate type (InputObjectType, ListOfType, NonNull, or scalar).
     *
     * @phpstan-param ValidatedFieldDefinitionConfig $config
     * @phpstan-param Path $path
     */
    public static function create(array $config, array $path = []): self
    {
        $resolvedType = self::_resolveType($config['type']);

        if ($resolvedType instanceof InputObjectType) {
            try {
                $type = new InputObjectValidationErrorType($config, $path);
            } catch (OverlySpecializedValidationErrorType $e) {
                $type = static::create(array_merge($config, ['type' => Type::id()]));
            }
        } else if ($resolvedType instanceof ListOfType) {
            try {
                $type = new ListOfValidationErrorType($config, $path);
            } catch (OverlySpecializedValidationErrorType $e) {
                $type = static::create(array_merge($config, ['type' => Type::id()]));
            }
        } else if ($resolvedType instanceof NonNull) {
            $config['type'] = static::_resolveType($config['type'], true);
            $type = static::create($config, $path);
        } else {
            if (!isset($config['validate']) && empty($config['required'])) {
                throw new NoValidatationFoundException();
            }
            if (!isset($config['errorCodes']) && empty($config['fields'])) {
                $type = TypeRegistry::validationError();
            } else {
                $type = new ValidationErrorType($config, $path);
            }
        }
        return static::_set($type);
    }

    /**
     * @param ValidatedFieldDefinitionConfig $field
     * @param mixed $value
     * @param ValidationSettings $settings
     *
     * @return mixed[]
     */
    public function validate(array $field, $value, array $settings): array
    {
        return [];
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
        return $type instanceof ScalarType || $type instanceof EnumType;
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

    public static function setTypeSetter(callable|null $typeSetter): void
    {
        static::$_typeSetter = $typeSetter;
    }


    /**
     * @template T of Type
     * @param T $type
     * @return T
     */
    static protected function _set(Type $type): Type
    {
        if (\is_callable(static::$_typeSetter)) {
            return call_user_func(static::$_typeSetter, $type);
        } else {
            return TypeRegistry::set($type);
        }
    }

    /**
     * @param ValidationErrorConfig $config
     * @param array<string|int,FieldDefinitionConfig>|non-empty-array<string|int,FieldDefinitionConfig> $fields
     * @phpstan-param array<string|int,FieldDefinitionConfig>|non-empty-array<string|int,FieldDefinitionConfig> $fields
     * @phpstan-param Path $path
     * @return array<string|int,FieldDefinitionConfig>|non-empty-array<string|int,FieldDefinitionConfig>
     * @phpstan-return array<string|int,FieldDefinitionConfig>|non-empty-array<string|int,FieldDefinitionConfig>
     * @throws \Exception
     */
    protected function _addCodeAndMessageFields(array $config, array $fields, array $path): array
    {
        if (isset($config['validate']) || !empty($config['required'])) {
            if (isset($config['errorCodes'])) {
                // error code. By default, this is an int, but if the user supplies the optional `errorCodes`
                // enum property, then it takes that type
                $type = new PhpEnumType($config['errorCodes']);
                $type->name = preg_replace('~ErrorCode$~', '', $type->name) . "ErrorCode";

                $fields[static::CODE_NAME] = [
                    'type' => static::_set($type),
                    'description' => 'An enumerated error code.',
                ];
            } else {
                $fields[static::CODE_NAME] = [
                    'type' => Type::int(),
                    'description' => 'A numeric error code. 0 on success, non-zero on failure.',
                ];
            }

            $fields[static::CODE_NAME]['resolve'] = static function ($error) {
                return $error[static::CODE_NAME] ?? null;
            };

            $fields[static::MESSAGE_NAME] = [
                'type' => Type::string(),
                'description' => 'An error message.',
                'resolve' => static function ($error) {
                    return $error[static::MESSAGE_NAME] ?? null;
                },
            ];
        } else {
            if (isset($config['errorCodes'])) {
                if (!isset($config['validate'])) {
                    throw new \Exception('If you specify errorCodes, you must also provide a validate callback');
                }
            }

        }
        return $fields;
    }

    /**
     * @param Path $path
     */
    protected function _nameFromPath(array $path): string
    {
        return implode('_', $path);
    }
}
