<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\UserErrorsType;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\UserErrorType\UserErrorsType;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidatedFieldDefinition;

final class NonNull extends TestBase
{
    public function testStringWrappedType(): void
    {
        $this->_checkSchema(UserErrorsType::create([
            'type' => Type::nonNull(Type::string()),
        ], ['upsertSku']), '
            schema {
              mutation: UpsertSkuError
            }
            
            "User errors for UpsertSku"
            type UpsertSkuError

        ');
    }

    public function testInputObjectWrappedType(): void
    {
        $this->_checkSchema(UserErrorsType::create([
            'type'=> Type::nonNull(new InputObjectType([
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
        ], ['upsertSku']), '
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
              fieldErrors: UpdateAuthor_FieldErrors
            }
            
            "Validation errors for UpdateAuthor"
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
            }

        ');
    }
}
