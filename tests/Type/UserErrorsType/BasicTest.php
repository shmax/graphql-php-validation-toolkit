<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Type\FieldDefinitionTest;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;

final class BasicTest extends FieldDefinitionTest
{
    public function testNoValidationOnSelf(): void
    {
        $type = new UserErrorsType([
            'type' => Type::id(),
        ], ['upsertSku']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: UpsertSkuError
            }
            
            """User errors for UpsertSku"""
            type UpsertSkuError {
            
            }

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }

    public function testNoType(): void
    {
        $this->expectExceptionMessage('You must specify a type for your field');
        UserErrorsType::create([
            'validate' => static function ($value) {
                return $value ? 0 : 1;
            },
        ], ['upsertSku']);
    }

    public function testArgCollisionWithResultName(): void
    {
        $this->expectExceptionMessage("'result' is a reserved field name at the root definition.");
        new ValidatedFieldDefinition([
            'type' => Type::boolean(),
            'name' => 'updateBook',
            'validate' => static function() {},
            'args' => [
                'result' => [

                ]
            ],
            'resolve' => static function (array $data) : bool {
                return !empty($data);
            },
        ]);
    }

    public function testArgCollisionWithValidName(): void
    {
        $this->expectExceptionMessage("'valid' is a reserved field name at the root definition.");
        new ValidatedFieldDefinition([
            'type' => Type::boolean(),
            'name' => 'updateBook',
            'validate' => static function() {},
            'args' => [
                'valid' => [

                ]
            ],
            'resolve' => static function (array $data) : bool {
                return !empty($data);
            },
        ]);
    }

    public function testRenameResultsField(): void
    {
        $this->_checkSchema(new ValidatedFieldDefinition([
            'type' => Type::boolean(),
            'name' => 'updateBook',
            'resultName' => '_result',
            'validate' => static function() {},
            'args' => [],
            'resolve' => static function (array $data) : bool {
                return !empty($data);
            },
        ]),
        '
            type Mutation {
              updateBook: UpdateBookResult
            }
            
            """User errors for UpdateBook"""
            type UpdateBookResult {
              """The payload, if any"""
              _result: Boolean
            
              """Whether all validation passed. True for yes, false for no."""
              valid: Boolean!
            
              """A numeric error code. 0 on success, non-zero on failure."""
              code: Int
            
              """An error message."""
              msg: String
            }

        ');
    }

    public function testRenameValidField(): void
    {
        $this->_checkSchema(new ValidatedFieldDefinition([
            'type' => Type::boolean(),
            'name' => 'updateBook',
            'validName' => '_valid',
            'validate' => static function() {},
            'args' => [],
            'resolve' => static function (array $data) : bool {
                return !empty($data);
            },
        ]),
            '
            type Mutation {
              updateBook: UpdateBookResult
            }
            
            """User errors for UpdateBook"""
            type UpdateBookResult {
              """The payload, if any"""
              result: Boolean
            
              """Whether all validation passed. True for yes, false for no."""
              _valid: Boolean!
            
              """A numeric error code. 0 on success, non-zero on failure."""
              code: Int
            
              """An error message."""
              msg: String
            }

        ');
    }

    public function testValidationWithNoErrorCodes(): void
    {
        $type = UserErrorsType::create([
            'validate' => static function ($value) {
                return $value ? 0 : 1;
            },
            'type' => Type::id(),
        ], ['upsertSku']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: UpsertSkuError
            }
            
            """User errors for UpsertSku"""
            type UpsertSkuError {
              """A numeric error code. 0 on success, non-zero on failure."""
              code: Int
            
              """An error message."""
              msg: String
            }

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }
}
