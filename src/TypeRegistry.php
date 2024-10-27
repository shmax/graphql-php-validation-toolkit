<?php

namespace GraphQlPhpValidationToolkit;

use ActiveRecord\Exception\RecordNotFound;
use Exception;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQlPhpValidationToolkit\Type\ErrorType\ListOfValidationErrorType;
use GraphQlPhpValidationToolkit\Type\ErrorType\ValidationErrorType;


class TypeRegistry
{
    /** @var array<Type> * */
    protected static array $types = [];

    // for the benefit of unit tests
    static function clearTypes(): void
    {
        static::$types = [];
    }

    /**
     * @throws Exception
     */
    public static function get(string $classname): callable
    {
        return static fn() => static::byClass($classname, static::cacheName($classname));
    }

    public static function set(Type $type): callable|Type
    {
        // @phpstan-ignore-next-line
        $cachedName = $type->name;
        if (!isset(self::$types[$cachedName])) {
            self::$types[$cachedName] = $type;
        }

        return self::$types[$cachedName];
    }

    protected static function byClass(string $classname, string $cacheName): Type
    {
        return static::fromCache($cacheName, function () use ($classname) {
            assert(class_exists($classname));
            return new $classname();
        });
    }

    public static function fromCache(string $cachename, callable $getter): Type
    {
        if (!isset(static::$types[$cachename])) {
            static::$types[$cachename] = $getter();
        }
        return static::$types[$cachename];
    }

    public static function cacheName(string $classname): string
    {
        $parts = explode("\\", $classname);
        $parts = preg_replace('~Type$~', '', $parts[count($parts) - 1]);
        assert(!is_array($parts));
        return strtolower($parts);
    }

    public static function validationError(): ValidationErrorType
    {
        /**
         * @var ValidationErrorType
         */
        return static::$types[static::cacheName(ValidationErrorType::class)] ??= new ValidationErrorType([
            'validate' => static fn() => null
        ]);
    }

    public static function listItemValidationError(): ValidationErrorType
    {
        /**
         * @var ValidationErrorType
         */
        return static::$types['listItemValidationError'] ??= new ValidationErrorType([
            'validate' => static fn() => null,
            'fields' => [
                ListOfValidationErrorType::PATH_NAME => ListOfValidationErrorType::pathFieldConfig(),
            ],
        ]);
    }
}
