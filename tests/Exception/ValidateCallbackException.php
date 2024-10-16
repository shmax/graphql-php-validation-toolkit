<?php declare(strict_types=1);

namespace Exception;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ErrorType;

final class ValidateCallbackException extends TestBase
{
    public function testIdThrows(): void
    {
        $this->expectExceptionMessage("You must provide at least one 'validate' callback or mark at least one field as 'required'.");

        ErrorType::create([
            'type' => Type::id(),
        ], ['upsertSku']);
    }

    public function testIdWithValidationDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();

        ErrorType::create([
            'type' => Type::id(),
            'validate' => static fn() => null
        ], ['upsertSku']);

    }

    public function testStringThrows(): void
    {
        $this->expectExceptionMessage("You must provide at least one 'validate' callback or mark at least one field as 'required'.");

        ErrorType::create([
            'type' => Type::string(),
        ], ['upsertSku']);
    }

    public function testStringWithValidationDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        ErrorType::create([
            'type' => Type::string(),
            'validate' => static fn() => null
        ], ['upsertSku']);
    }

    public function testIntThrows(): void
    {
        $this->expectExceptionMessage("You must provide at least one 'validate' callback or mark at least one field as 'required'.");

        ErrorType::create([
            'type' => Type::int(),
        ], ['upsertSku']);
    }

    public function testIntWithValidationDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        ErrorType::create([
            'type' => Type::int(),
            'validate' => static fn() => null
        ], ['upsertSku']);
    }

    public function testBooleanThrows(): void
    {
        $this->expectExceptionMessage("You must provide at least one 'validate' callback or mark at least one field as 'required'.");

        ErrorType::create([
            'type' => Type::boolean(),
        ], ['upsertSku']);
    }

    public function testBooleanWithValidationDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        ErrorType::create([
            'type' => Type::boolean(),
            'validate' => static fn() => null
        ], ['upsertSku']);
    }

    public function testFloatThrows(): void
    {
        $this->expectExceptionMessage("You must provide at least one 'validate' callback or mark at least one field as 'required'.");

        ErrorType::create([
            'type' => Type::float(),
        ], ['upsertSku']);
    }

    public function testFloatWithValidationDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        ErrorType::create([
            'type' => Type::float(),
            'validate' => static fn() => null
        ], ['upsertSku']);
    }

    public function testInputObjectThrows(): void
    {
        $this->expectExceptionMessage("You must provide at least one 'validate' callback or mark at least one field as 'required'.");
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
        $this->expectNotToPerformAssertions();
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
    }

    public function testInputObjectWithValidationOnFieldDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
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
    }

    public function testListOfFloatThrows(): void
    {
        $this->expectExceptionMessage("You must provide at least one 'validate' callback or mark at least one field as 'required'.");

        ErrorType::create([
            'type' => Type::listOf(Type::float()),
        ], ['upsertSku']);
    }

    public function testListOfValidatedFloatDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        ErrorType::create([
            'type' => Type::listOf(Type::float()),
            'items' => ['validate' => static fn() => null]
        ], ['upsertSku']);
    }

    public function testListOfStringThrows(): void
    {
        $this->expectExceptionMessage("You must provide at least one 'validate' callback or mark at least one field as 'required'.");

        ErrorType::create([
            'type' => Type::listOf(Type::string()),
        ], ['upsertSku']);
    }

    public function testListOfValidatedStringDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        ErrorType::create([
            'type' => Type::listOf(Type::string()),
            'items' => ['validate' => static fn() => null]
        ], ['upsertSku']);
    }

    public function testListOfIdThrows(): void
    {
        $this->expectExceptionMessage("You must provide at least one 'validate' callback or mark at least one field as 'required'.");

        ErrorType::create([
            'type' => Type::string(),
        ], ['upsertSku']);
    }

    public function testListOfValidatedIdDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        ErrorType::create([
            'type' => Type::listOf(Type::id()),
            'items' => ['validate' => static fn() => null]
        ], ['upsertSku']);
    }

    public function testListOfInputObjectThrows(): void
    {
        $this->expectExceptionMessage("You must provide at least one 'validate' callback or mark at least one field as 'required'.");
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
        $this->expectExceptionMessage("'items' is only supported for scalar types");
        ErrorType::create([
            'items' => ['validate' => static fn() => null],
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
