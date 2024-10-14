<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ErrorType;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ErrorType;

final class NonNull extends TestBase
{
    public function testStringWrappedType(): void
    {
        $this->_checkSchema(ErrorType::create([
            'type' => Type::nonNull(Type::string()),
            'validate' => static fn() => null
        ], ['upsertSku']), '
            schema {
              mutation: UpsertSkuError
            }
            
            "User errors for UpsertSku"
            type UpsertSkuError {
              "A numeric error code. 0 on success, non-zero on failure."
              __code: Int
            
              "An error message."
              __msg: String
            }

        ');
    }

    public function testInputObjectWrappedType(): void
    {
        $this->_checkSchema(ErrorType::create([
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
        ], ['upsertSku']), '
            schema {
              mutation: UpsertSkuError
            }
            
            "User errors for UpsertSku"
            type UpsertSkuError {
              "Error for firstName"
              firstName: UpsertSku_FirstNameError
            
              "Error for lastName"
              lastName: UpsertSku_LastNameError
            }
            
            "User errors for FirstName"
            type UpsertSku_FirstNameError {
              "A numeric error code. 0 on success, non-zero on failure."
              __code: Int
            
              "An error message."
              __msg: String
            }
            
            "User errors for LastName"
            type UpsertSku_LastNameError {
              "A numeric error code. 0 on success, non-zero on failure."
              __code: Int
            
              "An error message."
              __msg: String
            }

        ');
    }
}
