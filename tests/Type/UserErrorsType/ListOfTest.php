<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Type\FieldDefinitionTest;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use function count;

final class ListOfTest extends FieldDefinitionTest
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
            
            """User errors for UpsertSku"""
            type UpsertSkuError {
            
            }

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }

    public function testListOfStringWithValidationOnSelf(): void
    {
        $this->_checkTypes(UserErrorsType::create([
            'type' => Type::listOf(Type::string()),
            'errorCodes' => ['invalidPhoneNumber'],
            'validate' => static function (string $phoneNumber) {}
            ], ['phoneNumber'], true),
            [
                'PhoneNumberError' => '
                    type PhoneNumberError {
                      """An error code"""
                      code: PhoneNumberErrorCode
                    
                      """A natural language description of the issue"""
                      msg: String
                    
                      """A path describing this items\'s location in the nested array"""
                      path: [Int]
                    }
              '
            ]
        );
    }

    public function testListOfInputObjectWithValidationOnSelf(): void
    {
        $this->_checkTypes(UserErrorsType::create(
            [
                'type' => Type::listOf(new InputObjectType([
                    'name' => 'Address',
                    'fields' => [
                        'city' => [
                            'type' => Type::string()
                        ],
                        'zip' => [
                            'type' => Type::int()
                        ]
                    ]
                ])),
                'errorCodes' => ['notEnoughData'],
                'validate' => static function () {}
            ],
            ['address'],
            true
        ),
            [
                'AddressError' => '
                    type AddressError {
                      """An error code"""
                      code: AddressErrorCode
                    
                      """A natural language description of the issue"""
                      msg: String
                    
                      """A path describing this items\'s location in the nested array"""
                      path: [Int]
                    }
                '
            ]
        );
    }

    public function testListOfInputObjectWithValidationOnFields(): void
    {
        $this->_checkTypes(UserErrorsType::create(
            [
                'type' => Type::listOf(new InputObjectType([
                    'name' => 'Address',
                    'fields' => [
                        'city' => [
                            'type' => Type::string(),
                            'validate' => static function() {}
                        ],
                        'zip' => [
                            'type' => Type::int(),
                            'validate' => static function() {}
                        ]
                    ]
                ]))
            ],
            ['address'],
            true
        ),
            [
              'AddressError' => '
                    type AddressError {
                      """Validation errors for Address"""
                      suberrors: Address_FieldErrors
                    
                      """A path describing this items\'s location in the nested array"""
                      path: [Int]
                    }
              ',
              'Address_FieldErrors' => '
                    type Address_FieldErrors {
                      """Error for city"""
                      city: Address_CityError
                    
                      """Error for zip"""
                      zip: Address_ZipError
                    }
              '
            ]
        );
    }

    public function testListOfInputObjectWithValidationOnSelfAndFields(): void
    {
        $this->_checkTypes(UserErrorsType::create(
            [
                'validate' => static function() {},
                'type' => Type::listOf(new InputObjectType([
                    'name' => 'Address',
                    'fields' => [
                        'city' => [
                            'type' => Type::string(),
                            'validate' => static function() {}
                        ],
                        'zip' => [
                            'type' => Type::int(),
                            'validate' => static function() {}
                        ]
                    ]
                ]))
            ],
            ['address'],
            true
        ),
            [
                'AddressError' => '
                    type AddressError {
                      """A numeric error code. 0 on success, non-zero on failure."""
                      code: Int
                    
                      """An error message."""
                      msg: String
                    
                      """Validation errors for Address"""
                      suberrors: Address_FieldErrors
                    
                      """A path describing this items\'s location in the nested array"""
                      path: [Int]
                    }
                ',
                'Address_FieldErrors' => '
                    type Address_FieldErrors {
                      """Error for city"""
                      city: Address_CityError
                    
                      """Error for zip"""
                      zip: Address_ZipError
                    }
                '
            ]
        );
    }

    public function testListOfListOfListOfScalarWithValidation(): void
    {
        $this->_checkTypes(UserErrorsType::create(
            [
                'validate' => static function() {},
                'type' => Type::listOf(Type::listOf(Type::listOf(Type::string())))
            ],
            ['ids'],
            true
        ),
            [
                'IdsError' => '
                    type IdsError {
                      """A numeric error code. 0 on success, non-zero on failure."""
                      code: Int
                    
                      """An error message."""
                      msg: String
                    
                      """A path describing this items\'s location in the nested array"""
                      path: [Int]
                    }
                ',
            ]
        );
    }
}
