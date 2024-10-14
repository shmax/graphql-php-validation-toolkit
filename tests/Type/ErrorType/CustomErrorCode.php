<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ErrorType;

use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ErrorType;

enum ColorError
{
    case invalidColor;
    case badHue;
}

enum PersonErrorCode
{
    case PersonNotFound;
    case Retired;
}

final class CustomErrorCode extends TestBase
{
    public function testCustomEnumOnSelf(): void
    {
        $this->_checkSchema(ErrorType::create([
            'validate' => static fn() => null,
            'type' => Type::id(),
            'errorCodes' => ColorError::class
        ], ['palette']), '
            schema {
              mutation: PaletteError
            }
            
            "User errors for Palette"
            type PaletteError {
              "An enumerated error code."
              __code: Palette_ColorError
            
              "An error message."
              __msg: String
            }
            
            enum Palette_ColorError {
              invalidColor
              badHue
            }

        ');
    }

    public function testCustomEnumOnListOfIdType(): void
    {
        $this->_checkSchema(ErrorType::create([
            'type' => Type::listOf(Type::id()),
            'items' => [
                'validate' => static fn() => null,
                'errorCodes' => ColorError::class
            ],
        ], ['palette']), '
            schema {
              mutation: PaletteError
            }
            
            "User errors for Palette"
            type PaletteError {
              "Validation errors for each ID in the list"
              items: [PaletteError_IDError]
            }
            
            "User errors for ID"
            type PaletteError_IDError {
              "A path describing this item\'s location in the nested array"
              __path: [Int]
            
              "An enumerated error code."
              __code: PaletteError_ID_ColorError
            
              "An error message."
              __msg: String
            }
            
            enum PaletteError_ID_ColorError {
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
            ErrorType::create([
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
              mutation: UpdateBookError
            }
            
            "User errors for UpdateBook"
            type UpdateBookError {
              "Error for authorId"
              authorId: UpdateBook_AuthorIdError
            
              "Error for editorId"
              editorId: UpdateBook_EditorIdError
            }
            
            "User errors for AuthorId"
            type UpdateBook_AuthorIdError {
              "An enumerated error code."
              __code: UpdateBook_AuthorId_PersonErrorCode
            
              "An error message."
              __msg: String
            }
            
            enum UpdateBook_AuthorId_PersonErrorCode {
              PersonNotFound
              Retired
            }
            
            "User errors for EditorId"
            type UpdateBook_EditorIdError {
              "An enumerated error code."
              __code: UpdateBook_EditorId_PersonErrorCode
            
              "An error message."
              __msg: String
            }
            
            enum UpdateBook_EditorId_PersonErrorCode {
              PersonNotFound
              Retired
            }

        ');
    }

    /**
     * When there is a typesetter provided, we expect a shared name for custom error code
     */
    public function testFieldsWithErrorCodesAndTypeSetter(): void
    {
        $types = [];

        $this->_checkSchema(
            ErrorType::create([
                'typeSetter' => static function ($type) use (&$types): Type {
                    $types[$type->name] ??= $type;
                    return $types[$type->name];
                },

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
                  mutation: UpdateBookError
                }
                
                "User errors for UpdateBook"
                type UpdateBookError {
                  "Error for authorId"
                  authorId: UpdateBook_AuthorIdError
                
                  "Error for editorId"
                  editorId: UpdateBook_EditorIdError
                }
                
                "User errors for AuthorId"
                type UpdateBook_AuthorIdError {
                  "An enumerated error code."
                  __code: PersonErrorCode
                
                  "An error message."
                  __msg: String
                }
                
                enum PersonErrorCode {
                  PersonNotFound
                  Retired
                }
                
                "User errors for EditorId"
                type UpdateBook_EditorIdError {
                  "An enumerated error code."
                  __code: PersonErrorCode
                
                  "An error message."
                  __msg: String
                }

        ');
    }

//    public function testStringTypeWithErrorCodesAndTypeSetter(): void
//    {
//        $types = [];
//
//        $this->_checkSchema(
//            ErrorType::create([
//                'typeSetter' => static function ($type) use (&$types): Type {
//                    if (!isset($types[$type->name])) {
//                        $types[$type->name] = $type;
//                    }
//                    return $types[$type->name];
//                },
//
//                'type' => new StringType([
//                    'validate' => static fn () => null,
//                ]),
//            ], ['upsertSku']), ''
//        );
//    }
}
