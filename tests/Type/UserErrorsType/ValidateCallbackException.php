<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\UserErrorsType;

use GraphQL\Tests\Type\FieldDefinition;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Definition\UserErrorsType_bak;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;

final class ValidateCallbackException extends FieldDefinition
{
    public function testIdThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::id(),
        ], ['upsertSku']);
    }

    public function testStringThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::string(),
        ], ['upsertSku']);
    }

    public function testIntThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::int(),
        ], ['upsertSku']);
    }

    public function testBooleanThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::boolean(),
        ], ['upsertSku']);
    }

    public function testFloatThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::float(),
        ], ['upsertSku']);
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

    public function testListOfFloatThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' or 'validateItem' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::listOf(Type::float()),
        ], ['upsertSku']);
    }

    public function testListOfStringThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' or 'validateItem' callback somewhere in the tree.");

        UserErrorsType::create([
            'type' => Type::listOf(Type::string()),
        ], ['upsertSku']);
    }

    public function testListOfInputObjectThrows(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' or 'validateItem' callback somewhere in the tree.");
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
