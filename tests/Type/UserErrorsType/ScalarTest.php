<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\UserErrorsType;

use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\UserErrorType\UserErrorsType;

final class ScalarTest extends TestBase
{
    public function testId(): void
    {
        $this->_checkSchema(UserErrorsType::create([
            'validate' => static fn () => null,
            'type' => Type::id(),
        ], ['palette']), '
            schema {
              mutation: PaletteError
            }
            
            "User errors for Palette"
            type PaletteError {
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int

              "An error message."
              msg: String
            }

        ');
    }

    public function testBoolean(): void
    {
        $this->_checkSchema(UserErrorsType::create([
            'validate' => static fn () => null,
            'type' => Type::boolean(),
        ], ['palette']), '
            schema {
              mutation: PaletteError
            }
            
            "User errors for Palette"
            type PaletteError {
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int

              "An error message."
              msg: String
            }

        ');
    }

    public function testString(): void
    {
        $this->_checkSchema(UserErrorsType::create([
            'validate' => static fn () => null,
            'type' => Type::string(),
        ], ['palette']), '
            schema {
              mutation: PaletteError
            }
            
            "User errors for Palette"
            type PaletteError {
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int

              "An error message."
              msg: String
            }

        ');
    }

//    public function testStringType(): void
//    {
//        $this->_checkSchema(UserErrorsType::create([
//            'type' => new StringType([
//                'name' => 'foo',
//                'validate' => static fn () => null]
//            ),
//        ], ['palette']), '
//            schema {
//              mutation: PaletteError
//            }
//
//            "User errors for Palette"
//            type PaletteError {
//              "A numeric error code. 0 on success, non-zero on failure."
//              code: Int
//
//              "An error message."
//              msg: String
//            }
//
//        ');
//    }
}
