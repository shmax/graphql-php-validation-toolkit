<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\UserErrorsType;

use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ErrorType;

enum ColorErrors {
    case invalidColor;
    case badHue;
}

enum PersonErrorCode {
    case PersonNotFound;
    case Retired;
}

final class CustomErrorCode extends TestBase {
    public function testCustomEnumOnSelf() {
        $this->_checkSchema(ErrorType::create([
            'validate' => static fn () => null,
            'type' => Type::id(),
            'errorCodes' => ColorErrors::class
        ], ['palette']), '
            schema {
              mutation: PaletteError
            }
            
            "User errors for Palette"
            type PaletteError {
              "An enumerated error code."
              code: PaletteErrorCode
            
              "An error message."
              msg: String
            }
            
            enum PaletteErrorCode {
              invalidColor
              badHue
            }

        ');
    }

    public function testCustomEnumOnListOfIdType() {
        $this->_checkSchema(ErrorType::create([
            'type' => Type::listOf(new IDType([
                'validate' => static fn () => null,
                'errorCodes' => ColorErrors::class
            ])),
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
              path: [Int]
            
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
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
                            'validate' => static fn () => null,
                        ],
                        'editorId' => [
                            'errorCodes' => PersonErrorCode::class,
                            'type' => Type::id(),
                            'validate' => static fn () => null,
                        ],
                    ],
                ]),
        ], ['updateBook']), '
            schema {
              mutation: UpdateBookError
            }
            
            "User errors for UpdateBook"
            type UpdateBookError {
              "Validation errors for UpdateBook"
              fieldErrors: UpdateBook_FieldErrors
            }
            
            "Validation errors for UpdateBook"
            type UpdateBook_FieldErrors {
              "Error for authorId"
              authorId: UpdateBook_AuthorIdError
            
              "Error for editorId"
              editorId: UpdateBook_EditorIdError
            }
            
            "User errors for AuthorId"
            type UpdateBook_AuthorIdError {
              "An enumerated error code."
              code: UpdateBook_AuthorId_PersonErrorCode
            
              "An error message."
              msg: String
            }
            
            enum UpdateBook_AuthorId_PersonErrorCode {
              PersonNotFound
              Retired
            }
            
            "User errors for EditorId"
            type UpdateBook_EditorIdError {
              "An enumerated error code."
              code: UpdateBook_EditorId_PersonErrorCode
            
              "An error message."
              msg: String
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
                    if(!isset($types[$type->name])) {
                        $types[$type->name] = $type;
                    }
                    return $types[$type->name];
                },

                'type' => new InputObjectType([
                    'name' => 'updateBook',
                    'fields' => [
                        'authorId' => [
                            'errorCodes' => PersonErrorCode::class,
                            'type' => Type::id(),
                            'validate' => static fn () => null,
                        ],
                        'editorId' => [
                            'errorCodes' => PersonErrorCode::class,
                            'type' => Type::id(),
                            'validate' => static fn () => null,
                        ],
                    ],
                ]),
            ], ['updateBook']), '
                schema {
                  mutation: UpdateBookError
                }
                
                "User errors for UpdateBook"
                type UpdateBookError {
                  "Validation errors for UpdateBook"
                  fieldErrors: UpdateBook_FieldErrors
                }
                
                "Validation errors for UpdateBook"
                type UpdateBook_FieldErrors {
                  "Error for authorId"
                  authorId: UpdateBook_AuthorIdError
                
                  "Error for editorId"
                  editorId: UpdateBook_EditorIdError
                }
                
                "User errors for AuthorId"
                type UpdateBook_AuthorIdError {
                  "An enumerated error code."
                  code: PersonErrorCode
                
                  "An error message."
                  msg: String
                }
                
                enum PersonErrorCode {
                  PersonNotFound
                  Retired
                }
                
                "User errors for EditorId"
                type UpdateBook_EditorIdError {
                  "An enumerated error code."
                  code: PersonErrorCode
                
                  "An error message."
                  msg: String
                }

        ');
    }
}
