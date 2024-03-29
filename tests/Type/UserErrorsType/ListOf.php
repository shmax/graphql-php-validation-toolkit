<?php declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Type\FieldDefinition;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;

final class ListOf extends FieldDefinition
{
    public function testScalarTypeWithNoValidation(): void
    {
        $type = new UserErrorsType([
            'type' => Type::listOf(Type::id()),
        ], ['upsertSku']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: UpsertSkuError
            }
            
            "User errors for UpsertSku"
            type UpsertSkuError

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }

    public function testListOfStringWithValidationOnSelf(): void
    {
        $this->_checkTypes(
            UserErrorsType::create([
                'type' => Type::listOf(Type::string()),
                'validate' => static function (string $phoneNumber) {},
            ], ['phoneNumber'], true),
            [
                'PhoneNumberError' => '
                    type PhoneNumberError {
                      "A numeric error code. 0 on success, non-zero on failure."
                      code: Int
                    
                      "An error message."
                      msg: String
                    
                      "A path describing this item\'s location in the nested array"
                      path: [Int]
                    }
              ',
            ]
        );
    }

    public function testListOfInputObjectWithValidationOnSelf(): void
    {
        $this->_checkTypes(
            UserErrorsType::create(
            [
                'type' => Type::listOf(new InputObjectType([
                    'name' => 'Address',
                    'fields' => [
                        'city' => [
                            'type' => Type::string(),
                        ],
                        'zip' => [
                            'type' => Type::int(),
                        ],
                    ],
                ])),
                'validate' => static function () {},
            ],
            ['address'],
            true
        ),
            [
                'AddressError' => '
                    type AddressError {
                      "A numeric error code. 0 on success, non-zero on failure."
                      code: Int
                    
                      "An error message."
                      msg: String
                    
                      "A path describing this item\'s location in the nested array"
                      path: [Int]
                    }
                ',
            ]
        );
    }

    public function testListOfInputObjectWithValidationOnFields(): void
    {
        $this->_checkTypes(
            UserErrorsType::create(
            [
                'type' => Type::listOf(new InputObjectType([
                    'name' => 'Address',
                    'fields' => [
                        'city' => [
                            'type' => Type::string(),
                            'validate' => static function () {},
                        ],
                        'zip' => [
                            'type' => Type::int(),
                            'validate' => static function () {},
                        ],
                    ],
                ])),
            ],
            ['address'],
            true
        ),
            [
                'AddressError' => '
                    type AddressError {
                      "Validation errors for Address"
                      suberrors: Address_FieldErrors
                    
                      "A path describing this item\'s location in the nested array"
                      path: [Int]
                    }
              ',
                'Address_FieldErrors' => '
                    type Address_FieldErrors {
                      "Error for city"
                      city: Address_CityError
                    
                      "Error for zip"
                      zip: Address_ZipError
                    }
              ',
            ]
        );
    }

    public function testListOfInputObjectWithValidationOnSelfAndFields(): void
    {
        $this->_checkTypes(
            UserErrorsType::create(
            [
                'validate' => static function () {},
                'type' => Type::listOf(new InputObjectType([
                    'name' => 'Address',
                    'fields' => [
                        'city' => [
                            'type' => Type::string(),
                            'validate' => static function () {},
                        ],
                        'zip' => [
                            'type' => Type::int(),
                            'validate' => static function () {},
                        ],
                    ],
                ])),
            ],
            ['address'],
            true
        ),
            [
                'AddressError' => '
                    type AddressError {
                      "A numeric error code. 0 on success, non-zero on failure."
                      code: Int
                    
                      "An error message."
                      msg: String
                    
                      "Validation errors for Address"
                      suberrors: Address_FieldErrors
                    
                      "A path describing this item\'s location in the nested array"
                      path: [Int]
                    }
                ',
                'Address_FieldErrors' => '
                    type Address_FieldErrors {
                      "Error for city"
                      city: Address_CityError
                    
                      "Error for zip"
                      zip: Address_ZipError
                    }
                ',
            ]
        );
    }

    public function testListOfListOfListOfScalarWithValidation(): void
    {
        $this->_checkTypes(
            UserErrorsType::create(
            [
                'validate' => static function () {},
                'type' => Type::listOf(Type::listOf(Type::listOf(Type::string()))),
            ],
            ['ids'],
            true
        ),
            [
                'IdsError' => '
                    type IdsError {
                      "A numeric error code. 0 on success, non-zero on failure."
                      code: Int
                    
                      "An error message."
                      msg: String
                    
                      "A path describing this item\'s location in the nested array"
                      path: [Int]
                    }
                ',
            ]
        );
    }

    public function testValidateOnDeeplyNestedField(): void
    {
        $this->_checkTypes(
            UserErrorsType::create([
                'type' => Type::listOf(new InputObjectType([
                    'name' => 'book',
                    'fields' => [
                        'author' => [
                            'type' => Type::listOf(new InputObjectType([
                                'name' => 'address',
                                'fields' => [
                                    'zip' => [
                                        'validate' => static function () {},
                                        'type' => Type::listOf(Type::string()),
                                    ],
                                ],
                            ])),
                        ],
                    ],
                ])),
            ], ['updateBook'], true),
            [
                'UpdateBookError' => '
                    type UpdateBookError {
                      "Validation errors for UpdateBook"
                      suberrors: UpdateBook_FieldErrors
                    
                      "A path describing this item\'s location in the nested array"
                      path: [Int]
                    }
              ',
                'UpdateBook_FieldErrors' => '
                    type UpdateBook_FieldErrors {
                      "Error for author"
                      author: [UpdateBook_AuthorError]
                    }
              ',
                'UpdateBook_AuthorError' => '
                    type UpdateBook_AuthorError {
                      "Validation errors for Author"
                      suberrors: UpdateBook_Author_FieldErrors
                    
                      "A path describing this item\'s location in the nested array"
                      path: [Int]
                    }
              ',
                'UpdateBook_Author_FieldErrors' => '
                    type UpdateBook_Author_FieldErrors {
                      "Error for zip"
                      zip: [UpdateBook_Author_ZipError]
                    }
              ',
                'UpdateBook_Author_ZipError' => '
                    type UpdateBook_Author_ZipError {
                      "A numeric error code. 0 on success, non-zero on failure."
                      code: Int
                    
                      "An error message."
                      msg: String
                    
                      "A path describing this item\'s location in the nested array"
                      path: [Int]
                    }
              ',
            ]
        );
    }
}
