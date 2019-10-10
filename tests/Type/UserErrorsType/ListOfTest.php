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

    public function testListOfStringWithValidation(): void
    {
        $this->_checkSchema(new ValidatedFieldDefinition([
            'type' => Type::boolean(),
            'name' => 'updateAddressBook',
            'args' => [
                'phoneNumbers' => [
                    'type' => Type::listOf(Type::string()),
                    'errorCodes' => ['invalidPhoneNumber'],
                    'validate' => static function (string $phoneNumber) {
                        if (!is_numeric($phoneNumber)) {
                            return ['invalidPhoneNumber', 'You must provide a valid phone number'];
                        }
                        return 0;
                    }
                ],
            ],
            'resolve' => static function (array $data) : bool {
                return !empty($data);
            },
        ]),'
            type Mutation {
              updateAddressBook(phoneNumbers: [String]): UpdateAddressBookResult
            }
            
            """User errors for UpdateAddressBook"""
            type UpdateAddressBookResult {
              """The payload, if any"""
              result: Boolean
            
              """Whether all validation passed. True for yes, false for no."""
              valid: Boolean!
            
              """Validation errors for UpdateAddressBook"""
              suberrors: UpdateAddressBook_FieldErrors
            }
            
            """User Error"""
            type UpdateAddressBook_FieldErrors {
              """Error for phoneNumbers"""
              phoneNumbers: [UpdateAddressBook_PhoneNumbersError]
            }
            
            """User errors for PhoneNumbers"""
            type UpdateAddressBook_PhoneNumbersError {
              """An error code"""
              code: UpdateAddressBook_PhoneNumbersErrorCode
            
              """A natural language description of the issue"""
              msg: String
            
              """A path describing this items\'s location in the nested array"""
              path: [Int]
            }
            
            """Error code"""
            enum UpdateAddressBook_PhoneNumbersErrorCode {
              invalidPhoneNumber
            }

        ');
    }

    public function testListOfInputObjectWithValidation(): void
    {
        $this->_checkSchema(new ValidatedFieldDefinition([
            'type' => Type::boolean(),
            'name' => 'updateAddressBook',
            'args' => [
                'addresses' => [
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
                    'validate' => static function (array $address) {
                        if (empty($address['city'] && $address['zip'])) {
                            return ['notEnoughData', 'You must a city or a zip code'];
                        }
                        return 0;
                    }
                ],
            ],
            'resolve' => static function (array $data) : bool {
                return !empty($data);
            },
        ]),'
            input Address {
              city: String
              zip: Int
            }
            
            type Mutation {
              updateAddressBook(addresses: [Address]): UpdateAddressBookResult
            }
            
            """User errors for UpdateAddressBook"""
            type UpdateAddressBookResult {
              """The payload, if any"""
              result: Boolean
            
              """Whether all validation passed. True for yes, false for no."""
              valid: Boolean!
            
              """Validation errors for UpdateAddressBook"""
              suberrors: UpdateAddressBook_FieldErrors
            }
            
            """User errors for Addresses"""
            type UpdateAddressBook_AddressesError {
              """An error code"""
              code: UpdateAddressBook_AddressesErrorCode
            
              """A natural language description of the issue"""
              msg: String
            
              """A path describing this items\'s location in the nested array"""
              path: [Int]
            }
            
            """Error code"""
            enum UpdateAddressBook_AddressesErrorCode {
              notEnoughData
            }
            
            """User Error"""
            type UpdateAddressBook_FieldErrors {
              """Error for addresses"""
              addresses: [UpdateAddressBook_AddressesError]
            }

        ');
    }

    public function testListOfInputObjectWithValidationAndSubvalidation(): void
    {
        $this->_checkSchema(new ValidatedFieldDefinition([
            'type' => Type::boolean(),
            'name' => 'updateAddressBook',
            'args' => [
                'addresses' => [
                    'type' => Type::listOf(new InputObjectType([
                        'name' => 'Address',
                        'fields' => [
                            'city' => [
                                'errorCodes' => [
                                    'invalidCity'
                                ],
                                'validate' => function($city) {
                                    if ($city == "Toledo") {
                                        return ['invalidCity', "Sorry, Toledo is not allowed"];
                                    }
                                    return 0;
                                },
                                'type' => Type::string()
                            ],
                            'zip' => [
                                'errorCodes' => ['invalidZip', 'tooFarAway' ],
                                'validate' => function($zip) {
                                    if(!is_numeric($zip)) {
                                        return ["invalidZip", "Invalid zip format; should be numeric"];
                                    }
                                    return 0;
                                },
                                'type' => Type::int()
                            ]
                        ]
                    ])),
                    'errorCodes' => ['notEnoughData'],
                    'validate' => static function (array $address) {
                        if (empty($address['city'] && $address['zip'])) {
                            return ['notEnoughData', 'You must specify a city or a zip code'];
                        }
                        return 0;
                    }
                ],
            ],
            'resolve' => static function (array $data) : bool {
                return !empty($data);
            },
        ]),'
            input Address {
              city: String
              zip: Int
            }
            
            type Mutation {
              updateAddressBook(addresses: [Address]): UpdateAddressBookResult
            }
            
            """User errors for UpdateAddressBook"""
            type UpdateAddressBookResult {
              """The payload, if any"""
              result: Boolean
            
              """Whether all validation passed. True for yes, false for no."""
              valid: Boolean!
            
              """Validation errors for UpdateAddressBook"""
              suberrors: UpdateAddressBook_FieldErrors
            }
            
            """User errors for Addresses"""
            type UpdateAddressBook_AddressesError {
              """An error code"""
              code: UpdateAddressBook_AddressesErrorCode
            
              """A natural language description of the issue"""
              msg: String
            
              """Validation errors for Addresses"""
              suberrors: UpdateAddressBook_Addresses_FieldErrors
            
              """A path describing this items\'s location in the nested array"""
              path: [Int]
            }
            
            """Error code"""
            enum UpdateAddressBook_AddressesErrorCode {
              notEnoughData
            }
            
            """User errors for City"""
            type UpdateAddressBook_Addresses_CityError {
              """An error code"""
              code: UpdateAddressBook_Addresses_CityErrorCode
            
              """A natural language description of the issue"""
              msg: String
            }
            
            """Error code"""
            enum UpdateAddressBook_Addresses_CityErrorCode {
              invalidCity
            }
            
            """User Error"""
            type UpdateAddressBook_Addresses_FieldErrors {
              """Error for city"""
              city: UpdateAddressBook_Addresses_CityError
            
              """Error for zip"""
              zip: UpdateAddressBook_Addresses_ZipError
            }
            
            """User errors for Zip"""
            type UpdateAddressBook_Addresses_ZipError {
              """An error code"""
              code: UpdateAddressBook_Addresses_ZipErrorCode
            
              """A natural language description of the issue"""
              msg: String
            }
            
            """Error code"""
            enum UpdateAddressBook_Addresses_ZipErrorCode {
              invalidZip
              tooFarAway
            }
            
            """User Error"""
            type UpdateAddressBook_FieldErrors {
              """Error for addresses"""
              addresses: [UpdateAddressBook_AddressesError]
            }

        ');
    }

    public function testListOfListOfListOfScalarWithValidationOnSelfAndWrappedType(): void
    {
        $this->_checkSchema( new ValidatedFieldDefinition([
            'type' => Type::boolean(),
            'name' => 'updateAddressBook',
            'errorCodes' => ['atLeastOnePhoneNumberRequired'],
            'validate' => static function($data) {
                if(empty($data['phoneNumbers'])) {
                    return ['atLeastOnePhoneNumberRequired', "You must provide at least one phone number"];
                }
                return 0;
            },
            'args' => [
                'phoneNumbers' => [
                    'type' => Type::listOf(Type::listOf(Type::listOf(Type::id()))),
                    'errorCodes'=> ['mustHaveSevenDigits'],
                    'validate' => static function ($phoneNumber) {
                        if(strlen($phoneNumber) != 7) {
                            return ['mustHaveSevenDigits', "Phone numbers must have 7 digits"];
                        }
                        return 0;
                    },
                    'validateItem' => static function ($value) {
                        return $value ? 0 : 1;
                    }
                 ]
            ]
        ]), '
            type Mutation {
              updateAddressBook(phoneNumbers: [[[ID]]]): UpdateAddressBookResult
            }
            
            """Error code"""
            enum UpdateAddressBookErrorCode {
              atLeastOnePhoneNumberRequired
            }
            
            """User errors for UpdateAddressBook"""
            type UpdateAddressBookResult {
              """The payload, if any"""
              result: Boolean
            
              """Whether all validation passed. True for yes, false for no."""
              valid: Boolean!
            
              """An error code"""
              code: UpdateAddressBookErrorCode
            
              """A natural language description of the issue"""
              msg: String
            
              """Validation errors for UpdateAddressBook"""
              suberrors: UpdateAddressBook_FieldErrors
            }
            
            """User Error"""
            type UpdateAddressBook_FieldErrors {
              """Error for phoneNumbers"""
              phoneNumbers: [UpdateAddressBook_PhoneNumbersError]
            }
            
            """User errors for PhoneNumbers"""
            type UpdateAddressBook_PhoneNumbersError {
              """An error code"""
              code: UpdateAddressBook_PhoneNumbersErrorCode
            
              """A natural language description of the issue"""
              msg: String
            
              """A path describing this items\'s location in the nested array"""
              path: [Int]
            }
            
            """Error code"""
            enum UpdateAddressBook_PhoneNumbersErrorCode {
              mustHaveSevenDigits
            }

        ');
    }
}
