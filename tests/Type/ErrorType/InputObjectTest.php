<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ErrorType\InputObjectTest;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\ErrorType\ValidationErrorType;

enum StreetErrorCode
{
    case StreetNotFound;
}

enum PersonErrorCode
{
    case unknownPerson;
}

final class InputObjectTest extends TestBase
{
    /**
     * An exception should be thrown if errorCodes is provided without a validate callback"
     */
    public function testFieldsWithErrorCodesButNoValidate(): void
    {
        $this->expectExceptionMessage('If you specify errorCodes, you must also provide a validate callback');

        ValidationErrorType::create([
            'errorCodes' => PersonErrorCode::class,
            'type' => new InputObjectType([
                'name' => 'updateBook',
                'fields' => [
                    'authorId' => [
                        'type' => Type::id(),
                    ],
                ],
            ]),
        ], ['updateBook']);
    }


    /**
     * Generated error should include field errors at top level, but not _code or _msg
     */
    public function testValidateOnFieldsButNotOnSelf(): void
    {
        $this->_checkSchema(
            ValidationErrorType::create([
                'type' => new InputObjectType([
                    'name' => 'book',
                    'fields' => [
                        'title' => [
                            'type' => Type::string(),
                            'validate' => static function () {
                                return 0;
                            },
                        ],
                        'authorId' => [
                            'validate' => static function (int $authorId): int {
                                return ($authorId > 0) ? 0 : 1;
                            },
                            'type' => Type::id(),
                        ],
                    ],
                ]),
            ], ['updateBook']),
            '
                schema {
                  mutation: updateBook_ValidationError
                }
                
                "Validation error for UpdateBook"
                type updateBook_ValidationError {
                  "Error for title"
                  title: ValidationError
                
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

            '
        );
    }

    /**
     * If none of the fields are validated, then the error type should not include them
     */
    public function testValidateOnSelfButNotOnFields(): void
    {
        $this->_checkSchema(
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
            ], ['updateBook']),
            '
                schema {
                  mutation: updateBook_ValidationError
                }
                
                "Validation error for UpdateBook"
                type updateBook_ValidationError {
                  "A numeric error code. 0 on success, non-zero on failure."
                  _code: Int
                
                  "An error message."
                  _msg: String
                }

            '
        );
    }

    public function testValidateOnSelfAndOnFields(): void
    {
        $this->_checkSchema(
            ValidationErrorType::create([
                'validate' => static function () {
                },
                'type' => new InputObjectType([
                    'name' => 'book',
                    'fields' => [
                        'title' => [
                            'validate' => static function () {
                            },
                            'type' => Type::string(),
                        ],
                        'authorId' => [
                            'validate' => static function () {
                            },
                            'type' => Type::id(),
                        ],
                    ],
                ]),
            ], ['updateBook']),
            '
            schema {
              mutation: updateBook_ValidationError
            }
            
            "Validation error for UpdateBook"
            type updateBook_ValidationError {
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int
            
              "An error message."
              _msg: String

              "Error for title"
              title: ValidationError
            
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
            
            '
        );
    }

    public function testValidateOnDeeplyNestedField(): void
    {
        $this->_checkSchema(
            ValidationErrorType::create([
                'type' => new InputObjectType([
                    'name' => 'book',
                    'fields' => [
                        'author' => [
                            'type' => new InputObjectType([
                                'name' => 'address',
                                'fields' => [
                                    'zip' => [
                                        'validate' => static function () {
                                        },
                                        'type' => Type::string(),
                                    ],
                                    'street' => [
                                        'errorCodes' => StreetErrorCode::class,
                                        'validate' => static function () {
                                        },
                                        'type' => Type::string(),
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ]),
            ], ['updateBook']),
            '
                schema {
                  mutation: updateBook_ValidationError
                }
                
                "Validation error for UpdateBook"
                type updateBook_ValidationError {
                  "Error for author"
                  author: updateBook_author_ValidationError
                }
                
                "Validation error for Author"
                type updateBook_author_ValidationError {
                  "Error for zip"
                  zip: ValidationError

                  "Error for street"
                  street: StreetValidationError
                }

                "Validation error"
                type ValidationError {
                  "A numeric error code. 0 on success, non-zero on failure."
                  _code: Int
                
                  "An error message."
                  _msg: String
                }
                
                "Validation error for Street"
                type StreetValidationError {
                  "An enumerated error code."
                  _code: StreetErrorCode

                  "An error message."
                  _msg: String
                }
                
                enum StreetErrorCode {
                  StreetNotFound
                }

            '
        );
    }
}
