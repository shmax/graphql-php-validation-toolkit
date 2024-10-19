<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ErrorType;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ErrorType;

final class ListOf extends TestBase
{
    public function testScalarTypeWithNoValidation(): void
    {
        $this->expectExceptionMessage("You must provide at least one 'validate' callback or mark at least one field as 'required'.");
        ErrorType::create([
            'type' => Type::listOf(Type::id()),
        ], ['upsertSku']);
    }

    public function testCheckTypesOnListOfWithValidatedString(): void
    {
        $type = ErrorType::create([
            'type' => Type::listOf(Type::string()),
            'validate' => static fn() => null,
            'items' => [
                'validate' => static fn($str) => null
            ]
        ], ['upsertSku']);


        $this->_checkSchema($type, '
            schema {
              mutation: UpsertSkuError
            }
            
            "User errors for UpsertSku"
            type UpsertSkuError {
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int

              "An error message."
              _msg: String

              "Validation errors for each String in the list"
              _items: [UpsertSkuError_StringError]
            }
            
            "User errors for String"
            type UpsertSkuError_StringError {
              "A path describing this item\'s location in the nested array"
              _path: [Int]

              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int
            
              "An error message."
              _msg: String
            }

        ');
    }

    public function testCheckTypesOnListOfInputObjectWithValidation(): void
    {
        $type = ErrorType::create([
            'type' => Type::listOf(new InputObjectType([
                'name' => 'updateBook',
                'validate' => static fn($value) => null,
                'fields' => [
                    'authorId' => [
                        'type' => Type::id(),
                        'validate' => static fn($value) => null
                    ],
                ],
            ])),
            'validate' => static fn($value) => null
        ], ['upsertSku']);

        $this->_checkSchema($type, '
            schema {
              mutation: UpsertSkuError
            }

            "User errors for UpsertSku"
            type UpsertSkuError {
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int

              "An error message."
              _msg: String

              "Validation errors for each updateBook in the list"
              _items: [UpsertSkuError_UpdateBookError]
            }

            "User errors for UpdateBook"
            type UpsertSkuError_UpdateBookError {
              "A path describing this item\'s location in the nested array"
              _path: [Int]
            
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int
            
              "An error message."
              _msg: String
            
              "Error for authorId"
              authorId: UpsertSkuError_UpdateBook_AuthorIdError
            }
            
            "User errors for AuthorId"
            type UpsertSkuError_UpdateBook_AuthorIdError {
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int
            
              "An error message."
              _msg: String
            }

        ');
    }

    public function testCheckTypesOnListOfListOfWithValidatedString(): void
    {
        $type = ErrorType::create([
            'type' => Type::listOf(Type::listOf(Type::string())),
            'items' => [
                'validate' => static fn($str) => null
            ]
        ], ['upsertSku']);

        $this->_checkSchema($type, '
            schema {
              mutation: UpsertSkuError
            }
            
            "User errors for UpsertSku"
            type UpsertSkuError {
              "Validation errors for each String in the list"
              _items: [UpsertSkuError_StringError]
            }
            
            "User errors for String"
            type UpsertSkuError_StringError {
              "A path describing this item\'s location in the nested array"
              _path: [Int]

              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int
            
              "An error message."
              _msg: String
            }

        ');
    }

    public function testCheckTypesOnListOfWithValidatedBoolean(): void
    {
        $type = ErrorType::create([
            'type' => Type::listOf(Type::boolean()),
            'items' => [
                'validate' => static fn($str) => null
            ]
        ], ['upsertSku']);

        $this->_checkSchema($type, '
            schema {
              mutation: UpsertSkuError
            }
            
            "User errors for UpsertSku"
            type UpsertSkuError {
              "Validation errors for each Boolean in the list"
              _items: [UpsertSkuError_BooleanError]
            }
            
            "User errors for Boolean"
            type UpsertSkuError_BooleanError {
              "A path describing this item\'s location in the nested array"
              _path: [Int]

              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int
            
              "An error message."
              _msg: String
            }
            
        ');
    }
}
