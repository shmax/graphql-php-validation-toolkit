<?php declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Type\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ValidatedFieldDefinition;

final class NonNull extends FieldDefinition
{
    public function testStringWrappedType(): void
    {
        $this->_checkSchema(new ValidatedFieldDefinition([
            'type' => Type::boolean(),
            'name' => 'deleteAuthor',
            'args' => [
                'authorId' => [
                    'type' => Type::nonNull(Type::string()),
                    'validate' => static function (array $authorId) {
                        if (empty($authorId)) {
                            return [1, 'Invalid author id'];
                        }

                        return 0;
                    },
                ],
            ],
            'resolve' => static function (array $data): bool {
                return ! empty($data);
            },
        ]), '
            type Mutation {
              deleteAuthor(authorId: String!): DeleteAuthorResult
            }
            
            "User errors for DeleteAuthor"
            type DeleteAuthorResult {
              "The payload, if any"
              result: Boolean
            
              "Whether all validation passed. True for yes, false for no."
              valid: Boolean!
            
              "Validation errors for DeleteAuthor"
              suberrors: DeleteAuthor_FieldErrors
            }
            
            "User Error"
            type DeleteAuthor_FieldErrors {
              "Error for authorId"
              authorId: DeleteAuthor_AuthorIdError
            }
            
            "User errors for AuthorId"
            type DeleteAuthor_AuthorIdError {
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
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
                                'validate' => static function ($firstName) {
                                    if (strlen($firstName) > 100) {
                                        return 1;
                                    }

                                    return 0;
                                },
                            ],
                            'lastName' => [
                                'type' => Type::string(),
                                'description' => 'A last name',
                                'validate' => static function ($lastName) {
                                    if (strlen($lastName) > 100) {
                                        return 1;
                                    }

                                    return 0;
                                },
                            ],
                        ],
                    ])),
                    'validate' => static function (array $author) {
                        if (empty($author['firstName'] && empty($author['lastName']))) {
                            return [
                                1,
                                'Please provide at least a first or a last name',
                            ];
                        }

                        return 0;
                    },
                ],
            ],
            'resolve' => static function (array $data): bool {
                return ! empty($data);
            },
        ]), '
            type Mutation {
              updateAuthor(author: bookInput!): UpdateAuthorResult
            }
            
            input bookInput {
              "A first name"
              firstName: String
            
              "A last name"
              lastName: String
            }
            
            "User errors for UpdateAuthor"
            type UpdateAuthorResult {
              "The payload, if any"
              result: Boolean
            
              "Whether all validation passed. True for yes, false for no."
              valid: Boolean!
            
              "Validation errors for UpdateAuthor"
              suberrors: UpdateAuthor_FieldErrors
            }
            
            "User Error"
            type UpdateAuthor_FieldErrors {
              "Error for author"
              author: UpdateAuthor_AuthorError
            }
            
            "User errors for Author"
            type UpdateAuthor_AuthorError {
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            
              "Validation errors for Author"
              suberrors: UpdateAuthor_Author_FieldErrors
            }
            
            "User Error"
            type UpdateAuthor_Author_FieldErrors {
              "Error for firstName"
              firstName: UpdateAuthor_Author_FirstNameError
            
              "Error for lastName"
              lastName: UpdateAuthor_Author_LastNameError
            }
            
            "User errors for FirstName"
            type UpdateAuthor_Author_FirstNameError {
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            }
            
            "User errors for LastName"
            type UpdateAuthor_Author_LastNameError {
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            }

        ');
    }
}
