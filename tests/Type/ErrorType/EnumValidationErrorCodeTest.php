<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ErrorType\CustomErrorCodeWithTypeSetterTest;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\ErrorType\ValidationErrorType;

enum ColorErrorCode
{
    case invalidColor;
    case badHue;
}

enum PersonErrorCode
{
    case PersonNotFound;
    case Retired;
}

final class EnumValidationErrorCodeTest extends TestBase
{

    /**
     * Test validation error for a scalar type with a custom error code
     */
    public function testValidationErrorForScalarWithCustomErrorCode(): void
    {
        $this->_checkSchema(ValidationErrorType::create([
            'validate' => static fn() => null,
            'type' => Type::id(),
            'errorCodes' => ColorErrorCode::class
        ]), '
            schema {
              mutation: ColorValidationError
            }
            
            "Validation error"
            type ColorValidationError {
              "An enumerated error code."
              _code: ColorErrorCode
            
              "An error message."
              _msg: String
            }
            
            enum ColorErrorCode {
              invalidColor
              badHue
            }

        ');
    }

    /**
     * Test validation error for a scalar type with a custom error code that is wrapped in a list
     */
    public function testValidationErrorForScalarListItemWithCustomErrorCode(): void
    {
        $this->_checkSchema(ValidationErrorType::create([
            'validate' => static fn() => null,
            'type' => Type::listOf(Type::id()),
            'items' => [
                'errorCodes' => ColorErrorCode::class,
                'validate' => static fn() => null,
            ]
        ], ['palette']), '
            schema {
              mutation: palette_ListOfValidationError
            }
            
            "Validation error for Palette"
            type palette_ListOfValidationError {
              "A numeric error code. 0 on success, non-zero on failure."
              _code: Int
            
              "An error message."
              _msg: String
            
              "Validation errors for each ID in the list"
              _items: [ColorListItemValidationError]
            }
            
            "Validation error for Palette"
            type ColorListItemValidationError {
              "A path describing this item\'s location in the nested array"
              _path: [Int]
            
              "An enumerated error code."
              _code: ColorErrorCode
            
              "An error message."
              _msg: String
            }
            
            enum ColorErrorCode {
              invalidColor
              badHue
            }

        ');
    }
}