<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ErrorType;

use GraphQL\Type\Definition\PhpEnumType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ErrorType;

enum Animal
{
    case Mammal;
    case Bird;
}

final class Enum extends TestBase
{
    public function testId(): void
    {
        $this->_checkSchema(ErrorType::create([
            'validate' => static fn() => null,
            'type' => new PhpEnumType(Animal::class, "animals"),
        ], ['palette']), '
            schema {
              mutation: PaletteError
            }
            
            "User errors for Palette"
            type PaletteError {
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int

              "An error message."
              _msg: String
            }

        ');
    }
}
