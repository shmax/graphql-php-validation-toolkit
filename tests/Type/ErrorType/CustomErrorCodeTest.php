<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ErrorType\CustomErrorCodeWithTypeSetterTest;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidationErrorType;

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

final class CustomErrorCodeWithTypeSetterTest extends TestBase
{
    public function testCustomEnumOnSelf(): void
    {
        $this->_checkSchema(ValidationErrorType::create([
            'validate' => static fn() => null,
            'type' => Type::id(),
            'errorCodes' => ColorErrorCode::class
        ], ['palette']), '
            schema {
              mutation: PaletteValidationError
            }
            
            "Validation error for Palette"
            type PaletteValidationError {
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

    public function testCustomEnumOnListOfIdType(): void
    {
        $this->_checkSchema(ValidationErrorType::create([
            'type' => Type::listOf(Type::id()),
            'items' => [
                'validate' => static fn() => null,
                'errorCodes' => ColorErrorCode::class
            ],
        ], ['palette']), '
            schema {
              mutation: PaletteValidationError
            }
            
            "Validation error for Palette"
            type PaletteValidationError {
              "Validation errors for each ID in the list"
              _items: [PaletteValidationError_IDValidationError]
            }
            
            "Validation error for ID"
            type PaletteValidationError_IDValidationError {
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

    /**
     * When there is no typesetter provided, we expect unique name for each error code enum
     */
    public function testFieldsWithErrorCodes(): void
    {
        $this->_checkSchema(
            ValidationErrorType::create([
                'type' => new InputObjectType([
                    'name' => 'updateBook',
                    'fields' => [
                        'authorId' => [
                            'errorCodes' => PersonErrorCode::class,
                            'type' => Type::id(),
                            'validate' => static fn() => null,
                        ],
                        'editorId' => [
                            'errorCodes' => PersonErrorCode::class,
                            'type' => Type::id(),
                            'validate' => static fn() => null,
                        ],
                    ],
                ]),
            ], ['updateBook']), '
                schema {
                  mutation: UpdateBookValidationError
                }
                
                "Validation error for UpdateBook"
                type UpdateBookValidationError {
                  "Error for authorId"
                  authorId: UpdateBook_AuthorIdValidationError
                
                  "Error for editorId"
                  editorId: UpdateBook_EditorIdValidationError
                }
                
                "Validation error for AuthorId"
                type UpdateBook_AuthorIdValidationError {
                  "An enumerated error code."
                  _code: PersonErrorCode
                
                  "An error message."
                  _msg: String
                }
                
                enum PersonErrorCode {
                  PersonNotFound
                  Retired
                }
                
                "Validation error for EditorId"
                type UpdateBook_EditorIdValidationError {
                  "An enumerated error code."
                  _code: PersonErrorCode
                
                  "An error message."
                  _msg: String
                }

        ');
    }
}