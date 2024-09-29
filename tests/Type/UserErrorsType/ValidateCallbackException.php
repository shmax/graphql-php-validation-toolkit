<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\UserErrorsType;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQlPhpValidationToolkit\Tests\Type\FieldDefinition;
use GraphQlPhpValidationToolkit\Type\UserErrorType\UserErrorsType;

final class ValidateCallbackException extends FieldDefinition
{
    public function testIdThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::id(),
        ], ['upsertSku']);
    }

    public function testIdWithValidationDoesNotThrow(): void
    {
        UserErrorsType::create([
            'type' => Type::id(),
            'validate' => static fn () => null
        ], ['upsertSku']);

        $this->assertTrue(true);
    }

    public function testStringThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::string(),
        ], ['upsertSku']);
    }

    public function testStringWithValidationDoesNotThrow(): void
    {
        UserErrorsType::create([
            'type' => Type::string(),
            'validate' => static fn () => null
        ], ['upsertSku']);

        $this->assertTrue(true);

    }

    public function testIntThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::int(),
        ], ['upsertSku']);
    }

    public function testIntWithValidationDoesNotThrow(): void
    {
        UserErrorsType::create([
            'type' => Type::int(),
            'validate' => static fn () => null
        ], ['upsertSku']);

        $this->assertTrue(true);
    }

    public function testBooleanThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::boolean(),
        ], ['upsertSku']);
    }

    public function testBooleanWithValidationDoesNotThrow(): void
    {
        UserErrorsType::create([
            'type' => Type::boolean(),
            'validate' => static fn () => null
        ], ['upsertSku']);

        $this->assertTrue(true);
    }

    public function testFloatThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::float(),
        ], ['upsertSku']);
    }

    public function testFloatWithValidationDoesNotThrow(): void
    {
        UserErrorsType::create([
            'type' => Type::float(),
            'validate' => static fn () => null
        ], ['upsertSku']);

        $this->assertTrue(true);
    }

    public function testInputObjectThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");
        UserErrorsType::create([
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
        UserErrorsType::create([
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
            'validate' => static fn () => null
        ], ['upsertSku']);

        $this->assertTrue(true);
    }

    public function testInputObjectWithValidationOnFieldDoesNotThrow(): void
    {
        UserErrorsType::create([
            'type' => new InputObjectType([
                'name' => 'updateBook',
                'fields' => [
                    'authorId' => [
                        'type' => Type::id(),
                        'validate' => static fn () => null
                    ],
                    'publisherId' => [
                        'type' => Type::string(),
                    ]
                ],
            ]),
            'validate' => static fn () => null
        ], ['upsertSku']);

        $this->assertTrue(true);
    }

    public function testListOfFloatThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::listOf(Type::float()),
        ], ['upsertSku']);
    }

    public function testListOfValidatedFloatDoesNotThrow(): void
    {
        UserErrorsType::create([
            'type' => Type::listOf(new FloatType(['validate' => static fn () => null])),
        ], ['upsertSku']);
        $this->assertTrue(true);
    }

    public function testListOfStringThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::listOf(Type::string()),
        ], ['upsertSku']);
    }

    public function testListOfValidatedStringDoesNotThrow(): void
    {
        UserErrorsType::create([
            'type' => Type::listOf(new StringType(['validate' => static fn () => null])),
        ], ['upsertSku']);
        $this->assertTrue(true);
    }

    public function testListOfIdThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::string(),
        ], ['upsertSku']);
    }

    public function testListOfValidatedIdDoesNotThrow(): void
    {
        UserErrorsType::create([
            'type' => Type::listOf(new IDType(['validate' => static fn () => null])),
        ], ['upsertSku']);
        $this->assertTrue(true);
    }



    public function testListOfInputObjectThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");
        UserErrorsType::create([
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
