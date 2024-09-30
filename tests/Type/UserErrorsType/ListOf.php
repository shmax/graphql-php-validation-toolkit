<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\UserErrorsType;

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
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere in the tree.");
        ErrorType::create([
            'type' => Type::listOf(Type::id()),
        ], ['upsertSku']);
    }

    public function testCheckTypesOnListOfWithValidatedString() {
        $type = ErrorType::create([
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
              "A path describing this item\'s location in the nested array"
              path: [Int]

              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            }

        ');
    }

    public function testCheckTypesOnListOfInputObjectWithValidation() {
        $type = ErrorType::create([
            'type' => Type::listOf(new InputObjectType([
                'name' => 'updateBook',
                'validate' => static fn ($value) => null,
                'fields' => [
                    'authorId' => [
                        'type' => Type::id(),
                        'validate' => static fn ($value) => null
                    ],
                ],
            ])),
        ], ['upsertSku']);

        $this->_checkSchema($type, '
            schema {
              mutation: UpsertSkuError
            }

            "User errors for UpsertSku"
            type UpsertSkuError {
              "Validation errors for each updateBook in the list"
              items: [UpsertSkuError_UpdateBookError]
            }

            "User errors for UpdateBook"
            type UpsertSkuError_UpdateBookError {
              "A path describing this item\'s location in the nested array"
              path: [Int]
            
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            
              "Validation errors for UpdateBook"
              fieldErrors: UpsertSkuError_UpdateBook_FieldErrors
            }
            
            "Validation errors for UpdateBook"
            type UpsertSkuError_UpdateBook_FieldErrors {
              "Error for authorId"
              authorId: UpsertSkuError_UpdateBook_AuthorIdError
            }
            
            "User errors for AuthorId"
            type UpsertSkuError_UpdateBook_AuthorIdError {
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            }

        ');
    }

    public function testCheckTypesOnListOfListOfWithValidatedString() {
        $type = ErrorType::create([
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
              "A path describing this item\'s location in the nested array"
              path: [Int]

              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            }

        ');
    }

    public function testCheckTypesOnListOfWithValidatedBoolean() {
        $type = ErrorType::create([
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
              "A path describing this item\'s location in the nested array"
              path: [Int]

              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            }
            
        ');
    }
}
