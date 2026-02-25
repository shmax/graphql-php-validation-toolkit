<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ErrorType;

use GraphQL\Type\Definition\PhpEnumType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\TestBase;
use GraphQlPhpValidationToolkit\Type\ErrorType\ValidationErrorType;

enum Animal
{
    case Mammal;
    case Bird;
}

final class ValidationErrorTest extends TestBase
{
    public function testId(): void
    {
        $this->_checkSchema(ValidationErrorType::create([
            'validate' => static fn() => null,
            'type' => Type::id(),
        ]), '
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

    public function testBoolean(): void
    {
        $this->_checkSchema(ValidationErrorType::create([
            'validate' => static fn() => null,
            'type' => Type::id(),
        ]), '
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

    public function testString(): void
    {
        $this->_checkSchema(ValidationErrorType::create([
            'validate' => static fn() => null,
            'type' => Type::string(),
        ]), '
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

    public function testValidatedEnum(): void
    {
        $this->_checkSchema(ValidationErrorType::create([
            'validate' => static fn() => null,
            'type' => new PhpEnumType(Animal::class, "animals"),
        ]), '
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
}
