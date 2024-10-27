<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ErrorType\ListOf;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\ErrorType\ValidationErrorType;

enum PersonErrorCode
{
    case unknownPerson;
}

final class ListOf extends TestBase
{
    public function testScalarTypeWithNoValidation(): void
    {
        $this->expectExceptionMessage("You must provide at least one 'validate' callback or mark at least one field as 'required'.");
        ValidationErrorType::create([
            'type' => Type::listOf(Type::id()),
        ], ['upsertSku']);
    }

    /**
     * If we're only validating the entire list but not each item, then we can use a simple type
     */
    public function testValidationOnSelfButNotOnWrappedType(): void
    {
        $type = ValidationErrorType::create([
            'type' => Type::listOf(Type::string()),
            'validate' => static fn() => null,
        ], ['upsertSku']);


        $this->_checkSchema($type, '
            schema {
              mutation: ValidationError
            }
            
            "Validation error for UpsertSku"
            type ValidationError {
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int

              "An error message."
              _msg: String
            }

        ');
    }

    /**
     * For a scalar wrapped type, items type is ListItemValidationError
     */
    public function testListOfValidatedScalar(): void
    {
        $type = ValidationErrorType::create([
            'type' => Type::listOf(Type::string()),
            'validate' => static fn() => null,
            'items' => [
                'validate' => static fn($str) => null
            ]
        ], ['upsertSku']);


        $this->_checkSchema($type, '
            schema {
              mutation: upsertSku_ListOfValidationError
            }
            
            "Validation error for UpsertSku"
            type upsertSku_ListOfValidationError {
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int

              "An error message."
              _msg: String

              "Validation errors for each String in the list"
              _items: [ListItemValidationError]
            }
            
            "Validation error"
            type ListItemValidationError {
              "A path describing this item\'s location in the nested array"
              _path: [Int]

              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int
            
              "An error message."
              _msg: String
            }

        ');
    }

    /**
     * For a scalar wrapped type with errorCodes set, items type is <enum-stem>ValidationError
     */
    public function testListOfValidatedScalarWithEnumErrorCode(): void
    {
        $this->_checkSchema(ValidationErrorType::create([
            'type' => Type::listOf(Type::string()),
            'validate' => static fn() => null,
            'items' => [
                'validate' => static fn($str) => null,
                'errorCodes' => PersonErrorCode::class
            ]
        ], ['upsertSku']), '
            schema {
              mutation: upsertSku_ListOfValidationError
            }
            
            "Validation error for UpsertSku"
            type upsertSku_ListOfValidationError {
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int

              "An error message."
              _msg: String

              "Validation errors for each String in the list"
              _items: [PersonListItemValidationError]
            }
            
            "Validation error for UpsertSku"
            type PersonListItemValidationError {
              "A path describing this item\'s location in the nested array"
              _path: [Int]

              "An enumerated error code."
              _code: PersonErrorCode
            
              "An error message."
              _msg: String
            }
            
            enum PersonErrorCode {
              unknownPerson
            }

        ');
    }

    public function testCheckTypesOnListOfInputObjectWithValidation(): void
    {
        $type = ValidationErrorType::create([
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
              mutation: upsertSku_ListOfValidationError
            }

            "Validation error for UpsertSku"
            type upsertSku_ListOfValidationError {
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int

              "An error message."
              _msg: String

              "Validation errors for each updateBook in the list"
              _items: [upsertSku_ListItemValidationError]
            }

            "Validation error for UpsertSku"
            type upsertSku_ListItemValidationError {
              "A path describing this item\'s location in the nested array"
              _path: [Int]
            
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int
            
              "An error message."
              _msg: String
            
              "Error for authorId"
              authorId: ValidationError
            }
            
            "Validation error"
            type ValidationError {
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int
            
              "An error message."
              _msg: String
            }

        ');
    }

    public function testCheckTypesOnListOfListOfWithValidatedString(): void
    {
        $type = ValidationErrorType::create([
            'type' => Type::listOf(Type::listOf(Type::string())),
            'items' => [
                'validate' => static fn($str) => null
            ]
        ], ['upsertSku']);

        $this->_checkSchema($type, '
            schema {
              mutation: upsertSku_ListOfValidationError
            }
            
            "Validation error for UpsertSku"
            type upsertSku_ListOfValidationError {
              "Validation errors for each String in the list"
              _items: [ListItemValidationError]
            }
            
            "Validation error"
            type ListItemValidationError {
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
