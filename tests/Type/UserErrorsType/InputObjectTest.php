<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Type\FieldDefinitionTest;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Definition\ValidatedFieldDefinition;

final class InputObjectTest extends FieldDefinitionTest
{
    public function testFieldsWithErrorCodesButNoValidate(): void
    {
        $this->expectExceptionMessage('If you specify errorCodes, you must also provide a validate callback');

        new UserErrorsType([
            'type' => new InputObjectType([
                'name' => 'updateBook',
                'fields' => [
                    'authorId' => [
                        'errorCodes' => ['unknownAuthor'],
                        'type' => Type::id(),
                        'description' => 'An author Id',
                    ],
                ],
            ]),
        ], ['updateBook']);
    }

    public function testValidateSelfAndValidateFields(): void
    {
        $this->_checkSchema(new ValidatedFieldDefinition([
            'type' => Type::boolean(),
            'name' => 'updateBook',
            'args' => [
                'book' => [
                    'validate' => static function($book) {
                        return 0;
                    },
                    'type' => new InputObjectType([
                        'name' => 'book',
                        'fields' => [
                            'title' => [
                                'type' => Type::string(),
                                'validate' => static function() { return 0; }
                            ],
                            'authorId' => [
                                'errorCodes' => ['unknownAuthor'],
                                'validate' => static function (int $authorId) {
                                    return $authorId ? 0 : 1;
                                },
                                'type' => Type::id(),
                                'description' => 'An author Id',
                            ],
                        ],
                    ])
                ],
            ],
            'resolve' => static function (array $data) : bool {
                return !empty($data);
            },
        ]),
        '
            type Mutation {
              updateBook(book: book): UpdateBookResult
            }
            
            """User errors for UpdateBook"""
            type UpdateBookResult {
              """The payload, if any"""
              result: Boolean
            
              """Whether all validation passed. True for yes, false for no."""
              valid: Boolean!
            
              """Validation errors for UpdateBook"""
              fields: UpdateBook_FieldErrors
            }
            
            """User errors for Book"""
            type UpdateBook_BookError {
              """A numeric error code. 0 on success, non-zero on failure."""
              code: Int
            
              """An error message."""
              msg: String
            
              """Validation errors for Book"""
              fields: UpdateBook_Book_FieldErrors
            }
            
            """User errors for AuthorId"""
            type UpdateBook_Book_AuthorIdError {
              """An error code"""
              code: UpdateBook_Book_AuthorIdErrorCode
            
              """A natural language description of the issue"""
              msg: String
            }
            
            """Error code"""
            enum UpdateBook_Book_AuthorIdErrorCode {
              unknownAuthor
            }
            
            """User Error"""
            type UpdateBook_Book_FieldErrors {
              """Error for title"""
              title: UpdateBook_Book_TitleError
            
              """Error for authorId"""
              authorId: UpdateBook_Book_AuthorIdError
            }
            
            """User errors for Title"""
            type UpdateBook_Book_TitleError {
              """A numeric error code. 0 on success, non-zero on failure."""
              code: Int
            
              """An error message."""
              msg: String
            }
            
            """User Error"""
            type UpdateBook_FieldErrors {
              """Error for book"""
              book: UpdateBook_BookError
            }
            
            input book {
              title: String
            
              """An author Id"""
              authorId: ID
            }

        ');
    }
}
