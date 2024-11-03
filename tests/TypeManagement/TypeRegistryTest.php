<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\TypeManagement;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\TestBase;
use GraphQlPhpValidationToolkit\Type\ErrorType\ValidationErrorType;
use PHPUnit\Framework\Attributes\TestDox;

final class TypeRegistryTest extends TestBase
{
    #[TestDox("When supplying an outside TypeSetter, it collects types")]
    public function testNullableScalarValidationOnNullValueSuccess(): void
    {
        $types = [];
        ValidationErrorType::setTypeSetter(static function ($type) use (&$types) {
            $types[$type->name] ??= $type;
            return $types[$type->name];
        });

        ValidationErrorType::create([
            'validate' => static function () {
            },
            'type' => new InputObjectType([
                'name' => 'book',
                'fields' => [
                    'title' => [
                        'type' => Type::string(),
                    ],
                    'authorId' => [
                        'type' => Type::id(),
                    ],
                ],
            ]),
        ], ['updateBook']);

        static::assertEquals(count($types), 1);
    }
}
