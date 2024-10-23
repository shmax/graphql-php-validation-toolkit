<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ErrorType;

use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidationErrorType;

enum ColorErrorCode1
{
    case invalidColor;
    case badHue;
}

enum PersonErrorCode1
{
    case PersonNotFound;
    case Retired;
}

final class CustomErrorCodeWithTypeSetterTest extends TestBase
{
    public function testCustomEnumOnSelf(): void
    {
        $this->_checkSchema(ValidationErrorType::create([
            'validate' => static fn() => null,
            'type' => Type::id(),
            'errorCodes' => ColorErrorCode1::class
        ], ['palette']), '
            schema {
              mutation: PaletteValidationError
            }
            
            "Validation error for palette"
            type PaletteValidationError {
              "An enumerated error code."
              _code: Palette_ColorErrorCode1
            
              "An error message."
              _msg: String
            }
            
            enum Palette_ColorErrorCode1 {
              invalidColor
              badHue
            }

        ');
    }

    public function testCustomEnumOnListOfIdType(): void
    {
        $this->_checkSchema(ValidationErrorType::create([
            'type' => Type::listOf(Type::id()),
            'items' => [
                'validate' => static fn() => null,
                'errorCodes' => ColorErrorCode1::class
            ],
        ], ['palette']), '
            schema {
              mutation: PaletteValidationError
            }
            
            "Validation error for palette"
            type PaletteValidationError {
              "Validation errors for each ID in the list"
              _items: [PaletteValidationError_IDValidationError]
            }
            
            "Validation error for ID"
            type PaletteValidationError_IDValidationError {
              "A path describing this item\'s location in the nested array"
              _path: [Int]
            
              "An enumerated error code."
              _code: PaletteValidationError_ID_ColorErrorCode1
            
              "An error message."
              _msg: String
            }
            
            enum PaletteValidationError_ID_ColorErrorCode1 {
              invalidColor
              badHue
            }
            
        ');
    }

    /**
     * When there is no typesetter provided, we expect unique name for each error code enum
     */
    public function testFieldsWithErrorCodesAndNoTypeSetter(): void
    {
        $this->_checkSchema(
            ValidationErrorType::create([
                'typeSetter' => static function ($type) use (&$types): Type {
                    $types[$type->name] ??= $type;
                    return $types[$type->name];
                },
                'type' => new InputObjectType([
                    'name' => 'updateBook',
                    'fields' => [
                        'authorId' => [
                            'errorCodes' => PersonErrorCode1::class,
                            'type' => Type::id(),
                            'validate' => static fn() => null,
                        ],
                        'editorId' => [
                            'errorCodes' => PersonErrorCode1::class,
                            'type' => Type::id(),
                            'validate' => static fn() => null,
                        ],
                    ],
                ]),
            ], ['updateBook']), '
            schema {
              mutation: UpdateBookError
            }
            
            "User errors for UpdateBook"
            type UpdateBookError {
              "Error for authorId"
              authorId: PersonErrorCode1ValidationError
            
              "Error for editorId"
              editorId: PersonErrorCode1ValidationError
            }
            
            "User errors"
            type PersonErrorCode1ValidationError {
              "An enumerated error code."
              _code: PersonErrorCode
            
              "An error message."
              _msg: String
            }
        ');
    }
}