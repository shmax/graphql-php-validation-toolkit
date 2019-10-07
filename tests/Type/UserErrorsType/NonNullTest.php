<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Type\FieldDefinitionTest;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;

final class NonNullTest extends FieldDefinitionTest
{
    public function testStringWrappedType(): void
    {
        $this->_checkSchema(new ValidatedFieldDefinition([
            'type' => Type::boolean(),
            'name' => 'deleteAuthor',
            'args' => [
                'authorId' => [
                    'type' => Type::nonNull(Type::string()),
                    'errorCodes' => ['unknownAuthor'],
                    'validate' => static function (array $authorId) {
                        if (empty($authorId)) {
                            return ['unknownAuthor', "Invalid author id"];
                        }
                        return 0;
                    }
                ],
            ],
            'resolve' => static function (array $data) : bool {
                return !empty($data);
            },
        ]),'
            """User errors for DeleteAuthor"""
            type DeleteAuthorResult {
              """The payload, if any"""
              result: Boolean
            
              """Whether all validation passed. True for yes, false for no."""
              valid: Boolean!
            
              """Validation errors for DeleteAuthor"""
              suberrors: DeleteAuthor_FieldErrors
            }
            
            """User errors for AuthorId"""
            type DeleteAuthor_AuthorIdError {
              """An error code"""
              code: DeleteAuthor_AuthorIdErrorCode
            
              """A natural language description of the issue"""
              msg: String
            }
            
            """Error code"""
            enum DeleteAuthor_AuthorIdErrorCode {
              unknownAuthor
            }
            
            """User Error"""
            type DeleteAuthor_FieldErrors {
              """Error for authorId"""
              authorId: DeleteAuthor_AuthorIdError
            }
            
            type Mutation {
              deleteAuthor(authorId: String!): DeleteAuthorResult
            }

        ');
    }

    public function testInputObjectWrappedType(): void
    {

        $this->_checkSchema(new ValidatedFieldDefinition([
            'type' => Type::boolean(),
            'name' => 'updateAuthor',
            'args' => [
                'author' => [
                    'type' => Type::nonNull(new InputObjectType([
                        'name' => 'bookInput',
                        'fields' => [
                            'firstName' => [
                                'type' => Type::string(),
                                'description' => 'A first name',
                                'validate' => static function($firstName) {
                                    if(strlen($firstName > 100)) {
                                        return 1;
                                    }
                                    return 0;
                                }
                            ],
                            'lastName' => [
                                'type' => Type::string(),
                                'description' => 'A last name',
                                'validate' => static function($lastName) {
                                    if(strlen($lastName > 100)) {
                                        return 1;
                                    }
                                    return 0;
                                }
                            ],
                        ],
                    ])),
                    'errorCodes' => ['notEnoughInfo'],
                    'validate' => static function (array $author) {
                        if (empty($author['firstName'] && empty($author['lastName']))) {
                            return [
                                'notEnoughInfo',
                                "Please provide at least a first or a last name"
                            ];
                        }
                        return 0;
                    }
                ],
            ],
            'resolve' => static function (array $data) : bool {
                return !empty($data);
            },
        ]),'
            type Mutation {
              updateAuthor(author: bookInput!): UpdateAuthorResult
            }
            
            """User errors for UpdateAuthor"""
            type UpdateAuthorResult {
              """The payload, if any"""
              result: Boolean
            
              """Whether all validation passed. True for yes, false for no."""
              valid: Boolean!
            
              """Validation errors for UpdateAuthor"""
              suberrors: UpdateAuthor_FieldErrors
            }
            
            """User errors for Author"""
            type UpdateAuthor_AuthorError {
              """An error code"""
              code: UpdateAuthor_AuthorErrorCode
            
              """A natural language description of the issue"""
              msg: String
            
              """Validation errors for Author"""
              suberrors: UpdateAuthor_Author_FieldErrors
            }
            
            """Error code"""
            enum UpdateAuthor_AuthorErrorCode {
              notEnoughInfo
            }
            
            """User Error"""
            type UpdateAuthor_Author_FieldErrors {
              """Error for firstName"""
              firstName: UpdateAuthor_Author_FirstNameError
            
              """Error for lastName"""
              lastName: UpdateAuthor_Author_LastNameError
            }
            
            """User errors for FirstName"""
            type UpdateAuthor_Author_FirstNameError {
              """A numeric error code. 0 on success, non-zero on failure."""
              code: Int
            
              """An error message."""
              msg: String
            }
            
            """User errors for LastName"""
            type UpdateAuthor_Author_LastNameError {
              """A numeric error code. 0 on success, non-zero on failure."""
              code: Int
            
              """An error message."""
              msg: String
            }
            
            """User Error"""
            type UpdateAuthor_FieldErrors {
              """Error for author"""
              author: UpdateAuthor_AuthorError
            }
            
            input bookInput {
              """A first name"""
              firstName: String
            
              """A last name"""
              lastName: String
            }

        ');

    }
}
