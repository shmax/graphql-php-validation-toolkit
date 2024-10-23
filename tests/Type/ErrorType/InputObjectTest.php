<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ErrorType;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidationErrorType;

enum AuthorErrorTest
{
    case AuthorNotFound;
}

final class InputObjectTest extends TestBase
{
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
                  mutation: UpdateBookError
                }
                
                "User errors for UpdateBook"
                type UpdateBookError {
                  "Error for title"
                  title: ValidationError
                
                  "Error for authorId"
                  authorId: ValidationError
                }
                
                "User errors"
                type ValidationError {
                  "A numeric error code. 0 on success, non-zero on failure."
                  _code: Int
                
                  "An error message."
                  _msg: String
                }

            '
        );
    }

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
                  mutation: UpdateBookError
                }
                
                "User errors for UpdateBook"
                type UpdateBookError {
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
              mutation: UpdateBookError
            }
            
            "User errors for UpdateBook"
            type UpdateBookError {
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int
            
              "An error message."
              _msg: String

              "Error for title"
              title: ValidationError
            
              "Error for authorId"
              authorId: ValidationError
            }
            
            "User errors"
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
                                ],
                            ]),
                        ],
                    ],
                ]),
            ], ['updateBook']),
            '
                schema {
                  mutation: UpdateBookError
                }
                
                "User errors for UpdateBook"
                type UpdateBookError {
                  "Error for author"
                  author: UpdateBook_AuthorError
                }
                
                "User errors for Author"
                type UpdateBook_AuthorError {
                  "Error for zip"
                  zip: ValidationError
                }
                
                "User errors"
                type ValidationError {
                  "A numeric error code. 0 on success, non-zero on failure."
                  _code: Int
                
                  "An error message."
                  _msg: String
                }

            '
        );
    }
}
