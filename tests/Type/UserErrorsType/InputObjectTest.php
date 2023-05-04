<?php declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Type\FieldDefinitionTest;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
enum AuthorErrorTest {
    case AuthorNotFound;
}

final class InputObjectTest extends FieldDefinitionTest
{
    public function testFieldsWithErrorCodesButNoValidate(): void
    {
        $this->expectExceptionMessage('If you specify errorCodes, you must also provide a validate callback');

        new UserErrorsType([
            'errorCodes' => AuthorErrorTest::class,
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
        $this->_checkTypes(
            UserErrorsType::create([
                'type' => new InputObjectType([
                    'name' => 'book',
                    'fields' => [
                        'title' => [
                            'type' => Type::string(),
                            'validate' => static function () { return 0; },
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
            [
                'Mutation' => '
                    type Mutation {
                      UpdateBookError: UpdateBookError
                    }
              ',
                'UpdateBookError' => '
                    type UpdateBookError {
                      "Error for title"
                      title: UpdateBook_TitleError
                    
                      "Error for authorId"
                      authorId: UpdateBook_AuthorIdError
                    }
              ',
                'UpdateBook_TitleError' => '
                    type UpdateBook_TitleError {
                      "A numeric error code. 0 on success, non-zero on failure."
                      code: Int
                    
                      "An error message."
                      msg: String
                    }
              ',
                'UpdateBook_AuthorIdError' => '
                    type UpdateBook_AuthorIdError {
                      "A numeric error code. 0 on success, non-zero on failure."
                      code: Int
                    
                      "An error message."
                      msg: String
                    }
              ',
            ]
        );
    }

    public function testValidateOnSelfButNotOnFields(): void
    {
        $this->_checkTypes(
            UserErrorsType::create([
                'validate' => static function () {},
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
            [
                'UpdateBookError' => '
                    type UpdateBookError {
                      "A numeric error code. 0 on success, non-zero on failure."
                      code: Int
                    
                      "An error message."
                      msg: String
                    }
              ',
            ]
        );
    }

    public function testValidateOnSelfAndOnFields(): void
    {
        $this->_checkTypes(
            UserErrorsType::create([
                'validate' => static function () {},
                'type' => new InputObjectType([
                    'name' => 'book',
                    'fields' => [
                        'title' => [
                            'validate' => static function () {},
                            'type' => Type::string(),
                        ],
                        'authorId' => [
                            'validate' => static function () {},
                            'type' => Type::id(),
                        ],
                    ],
                ]),
            ], ['updateBook']),
            [
                'UpdateBookError' => '
                    type UpdateBookError {
                      "A numeric error code. 0 on success, non-zero on failure."
                      code: Int
                    
                      "An error message."
                      msg: String
                    
                      "Validation errors for UpdateBook"
                      suberrors: UpdateBook_FieldErrors
                    }
                ',
                'UpdateBook_FieldErrors' => '
                    type UpdateBook_FieldErrors {
                      "Error for title"
                      title: UpdateBook_TitleError
                    
                      "Error for authorId"
                      authorId: UpdateBook_AuthorIdError
                    }
                ',
                'UpdateBook_TitleError' => '
                    type UpdateBook_TitleError {
                      "A numeric error code. 0 on success, non-zero on failure."
                      code: Int
                    
                      "An error message."
                      msg: String
                    }
                ',
                'UpdateBook_AuthorIdError' => '
                    type UpdateBook_AuthorIdError {
                      "A numeric error code. 0 on success, non-zero on failure."
                      code: Int
                    
                      "An error message."
                      msg: String
                    }
                ',
            ]
        );
    }

    public function testValidateOnDeeplyNestedField(): void
    {
        $this->_checkTypes(
            UserErrorsType::create([
                'type' => new InputObjectType([
                    'name' => 'book',
                    'fields' => [
                        'author' => [
                            'type' => new InputObjectType([
                                'name' => 'address',
                                'fields' => [
                                    'zip' => [
                                        'validate' => static function () {},
                                        'type' => Type::string(),
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ]),
            ], ['updateBook']),
            [
                'UpdateBookError' => '
                    type UpdateBookError {
                      "Error for author"
                      author: UpdateBook_AuthorError
                    }
              ',
                'UpdateBook_AuthorError' => '
                    type UpdateBook_AuthorError {
                      "Error for zip"
                      zip: UpdateBook_Author_ZipError
                    }
              ',
                'UpdateBook_Author_ZipError' => '
                    type UpdateBook_Author_ZipError {
                      "A numeric error code. 0 on success, non-zero on failure."
                      code: Int
                    
                      "An error message."
                      msg: String
                    }
              ',
            ]
        );
    }
}
