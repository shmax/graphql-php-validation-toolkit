<?php declare(strict_types=1);

namespace Exception;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ErrorType;

final class ValidateCallbackException extends TestBase
{
    public function testIdThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        ErrorType::create([
            'type' => Type::id(),
        ], ['upsertSku']);
    }

    public function testIdWithValidationDoesNotThrow(): void
    {
        ErrorType::create([
            'type' => Type::id(),
            'validate' => static fn() => null
        ], ['upsertSku']);

        $this->assertTrue(true);
    }

    public function testStringThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        ErrorType::create([
            'type' => Type::string(),
        ], ['upsertSku']);
    }

    public function testStringWithValidationDoesNotThrow(): void
    {
        ErrorType::create([
            'type' => Type::string(),
            'validate' => static fn() => null
        ], ['upsertSku']);

        $this->assertTrue(true);

    }

    public function testIntThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        ErrorType::create([
            'type' => Type::int(),
        ], ['upsertSku']);
    }

    public function testIntWithValidationDoesNotThrow(): void
    {
        ErrorType::create([
            'type' => Type::int(),
            'validate' => static fn() => null
        ], ['upsertSku']);

        $this->assertTrue(true);
    }

    public function testBooleanThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        ErrorType::create([
            'type' => Type::boolean(),
        ], ['upsertSku']);
    }

    public function testBooleanWithValidationDoesNotThrow(): void
    {
        ErrorType::create([
            'type' => Type::boolean(),
            'validate' => static fn() => null
        ], ['upsertSku']);

        $this->assertTrue(true);
    }

    public function testFloatThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        ErrorType::create([
            'type' => Type::float(),
        ], ['upsertSku']);
    }

    public function testFloatWithValidationDoesNotThrow(): void
    {
        ErrorType::create([
            'type' => Type::float(),
            'validate' => static fn() => null
        ], ['upsertSku']);

        $this->assertTrue(true);
    }

    public function testInputObjectThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");
        ErrorType::create([
            'type' => new InputObjectType([
                'name' => 'updateBook',
                'fields' => [
                    'authorId' => [
                        'type' => Type::id(),
                    ],
                    'publisherId' => [
                        'type' => Type::string(),
                    ]
                ],
            ]),
        ], ['upsertSku']);
    }

    public function testInputObjectWithValidationDoesNotThrow(): void
    {
        ErrorType::create([
            'type' => new InputObjectType([
                'name' => 'updateBook',
                'fields' => [
                    'authorId' => [
                        'type' => Type::id(),
                    ],
                    'publisherId' => [
                        'type' => Type::string(),
                    ]
                ],
            ]),
            'validate' => static fn() => null
        ], ['upsertSku']);

        $this->assertTrue(true);
    }

    public function testInputObjectWithValidationOnFieldDoesNotThrow(): void
    {
        ErrorType::create([
            'type' => new InputObjectType([
                'name' => 'updateBook',
                'fields' => [
                    'authorId' => [
                        'type' => Type::id(),
                        'validate' => static fn() => null
                    ],
                    'publisherId' => [
                        'type' => Type::string(),
                    ]
                ],
            ]),
            'validate' => static fn() => null
        ], ['upsertSku']);

        $this->assertTrue(true);
    }

    public function testListOfFloatThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        ErrorType::create([
            'type' => Type::listOf(Type::float()),
        ], ['upsertSku']);
    }

    public function testListOfValidatedFloatDoesNotThrow(): void
    {
        ErrorType::create([
            'type' => Type::listOf(Type::float()),
            'item' => ['validate' => static fn() => null]
        ], ['upsertSku']);
        $this->assertTrue(true);
    }

    public function testListOfStringThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        ErrorType::create([
            'type' => Type::listOf(Type::string()),
        ], ['upsertSku']);
    }

    public function testListOfValidatedStringDoesNotThrow(): void
    {
        ErrorType::create([
            'type' => Type::listOf(Type::string()),
            'item' => ['validate' => static fn() => null]
        ], ['upsertSku']);
        $this->assertTrue(true);
    }

    public function testListOfIdThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        ErrorType::create([
            'type' => Type::string(),
        ], ['upsertSku']);
    }

    public function testListOfValidatedIdDoesNotThrow(): void
    {
        ErrorType::create([
            'type' => Type::listOf(Type::id()),
            'item' => ['validate' => static fn() => null]
        ], ['upsertSku']);
        $this->assertTrue(true);
    }

    public function testListOfInputObjectThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");
        ErrorType::create([
            'type' => Type::listOf(new InputObjectType([
                'name' => 'updateBook',
                'fields' => [
                    'authorId' => [
                        'type' => Type::id(),
                    ],
                    'publisherId' => [
                        'type' => Type::string(),
                    ]
                ],
            ])),
        ], ['upsertSku']);
    }

    public function testItemValidationOnListOfInputObjectThrows(): void
    {
        $this->expectExceptionMessage("'item' is only supported for scalar types");
        ErrorType::create([
            'item' => ['validate' => static fn() => null],
            'type' => Type::listOf(new InputObjectType([
                'name' => 'updateBook',
                'fields' => [
                    'authorId' => [
                        'type' => Type::id(),
                    ],
                    'publisherId' => [
                        'type' => Type::string(),
                    ]
                ],
            ])),
        ], ['upsertSku']);
    }
}
