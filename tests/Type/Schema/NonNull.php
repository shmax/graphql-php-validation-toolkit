<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ErrorType;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\ErrorType\ValidationErrorType;

final class NonNull extends TestBase
{
    public function testStringWrappedType(): void
    {
        $this->_checkSchema(ValidationErrorType::create([
            'type' => Type::nonNull(Type::string()),
            'validate' => static fn() => null
        ], ['upsertSku']), '
            schema {
              mutation: ValidationError
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

    /**
     * Test validation error for a nonNull-wrapped InputObjectType with validation on fields
     */
    public function testInputObjectWrappedType(): void
    {
        $this->_checkSchema(ValidationErrorType::create([
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
              mutation: upsertSku_ValidationError
            }
            
            "Validation error for UpsertSku"
            type upsertSku_ValidationError {
              "Error for firstName"
              firstName: ValidationError
            
              "Error for lastName"
              lastName: ValidationError
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
}
