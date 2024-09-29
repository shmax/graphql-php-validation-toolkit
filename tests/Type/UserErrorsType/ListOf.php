<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\UserErrorsType;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\FieldDefinition;
use GraphQlPhpValidationToolkit\Type\UserErrorType\UserErrorsType;

final class ListOf extends FieldDefinition
{
    public function testScalarTypeWithNoValidation(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");
        UserErrorsType::create([
            'type' => Type::listOf(Type::id()),
        ], ['upsertSku']);
    }

    public function testCheckTypesOnListOfWithValidatedString() {
        $type = UserErrorsType::create([
            'type' => Type::listOf(new StringType([
                'validate' => static fn ($str) => null
            ])),
        ], ['upsertSku']);

        $this->_checkSchema($type, '
            schema {
              mutation: UpsertSkuError
            }
            
            "User errors for UpsertSku"
            type UpsertSkuError {
              "Validation errors for each String in the list"
              items: [UpsertSkuError_StringError]
            }
            
            "User errors for String"
            type UpsertSkuError_StringError {
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            
              "A path describing this item\'s location in the nested array"
              path: [Int]
            }

        ');
    }

    public function testCheckTypesOnListOfListOfWithValidatedString() {
        $type = UserErrorsType::create([
            'type' => Type::listOf(Type::listOf(new StringType([
                'validate' => static fn ($str) => null
            ]))),
        ], ['upsertSku']);

        $this->_checkSchema($type, '
            schema {
              mutation: UpsertSkuError
            }
            
            "User errors for UpsertSku"
            type UpsertSkuError {
              "Validation errors for each String in the list"
              items: [UpsertSkuError_StringError]
            }
            
            "User errors for String"
            type UpsertSkuError_StringError {
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            }

        ');
    }

    public function testCheckTypesOnListOfWithValidatedBoolean() {
        $type = UserErrorsType::create([
            'type' => Type::listOf(new BooleanType(['validate' => static fn ($str) => null])),
        ], ['upsertSku']);

        $this->_checkSchema($type, '
            schema {
              mutation: UpsertSkuError
            }
            
            "User errors for UpsertSku"
            type UpsertSkuError {
              "Validation errors for each Boolean in the list"
              items: [UpsertSkuError_BooleanError]
            }
            
            "User errors for Boolean"
            type UpsertSkuError_BooleanError {
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            }
            
        ');
    }
}
