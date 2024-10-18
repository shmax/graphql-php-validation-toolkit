<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidatedFieldDefinition;
use GraphQlPhpValidationToolkit\Type\ValidatedStringType;
use PHPUnit\Framework\TestCase;

final class ListOfScalarValidationTest extends TestBase
{
    public function testListOfStringType(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
                'name' => 'savePhoneNumbers',
                'type' => Type::boolean(),
                'args' => [
                    'phoneNumbers' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Enter a list of names. We'll validate each one",
                        'items' => ['validate' => static function ($value) {
                            return strlen($value) <= 7 ? 0 : 1;
                        }]
                    ],
                ],
                'resolve' => static function ($value): bool {
                    return true;
                },
            ]),
            Utils::nowdoc('
                mutation SavePhoneNumbers($phoneNumbers: [String]) {
                    savePhoneNumbers (
                        phoneNumbers: $phoneNumbers
                    ) {
                        __valid
                        phoneNumbers {
                            items {
                                __code
                                __msg
                                __path
                            }
                        }
                        __result
                    }
                }
            '),
            [
                'phoneNumbers' => [
                    '1',
                    '2',
                    '3'
                ],
            ],
            [
                '__valid' => true,
                '__result' => null,
                'phoneNumbers' => null
            ]
        );
    }

    public function testListOfListOfStringTypeValid(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
                'name' => 'savePhoneNumbers',
                'type' => Type::boolean(),
                'args' => [
                    'phoneNumbers' => [
                        'type' => Type::listOf(Type::listOf(Type::string())),
                        'description' => "Enter a list of names. We'll validate each one",
                        'items' => ['validate' => static function ($value) {
                            return strlen($value) <= 7 ? 0 : 1;
                        }]
                    ],
                ],
                'resolve' => static function ($value): bool {
                    return true;
                },
            ]),
            Utils::nowdoc('
                mutation SavePhoneNumbers($phoneNumbers: [[String]]) {
                    savePhoneNumbers (
                        phoneNumbers: $phoneNumbers
                    ) {
                        __valid
                        phoneNumbers {
                            items {
                                __code
                                __msg
                                __path
                            }
                        }
                        __result
                    }
                }
            '),
            [
                'phoneNumbers' => [[
                    '1',
                    '2',
                    '3'
                ]],
            ],
            [
                '__valid' => true,
                '__result' => null,
                'phoneNumbers' => null
            ]
        );
    }

    public function testListOfListOfStringTypeInvalid(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
                'name' => 'savePhoneNumbers',
                'type' => Type::boolean(),
                'args' => [
                    'phoneNumbers' => [
                        'type' => Type::listOf(Type::listOf(Type::string())),
                        'description' => "Enter a list of names. We'll validate each one",
                        'items' => ['validate' => static function ($number) {
                            return preg_match('/^[0-9\-]+$/', $number) === 1 ? 0 : [1, "only hyphens and digits are allowed"];
                        }]
                    ],
                ],
                'resolve' => static function ($value): bool {
                    return true;
                },
            ]),
            Utils::nowdoc('
                mutation SavePhoneNumbers($phoneNumbers: [[String]]) {
                    savePhoneNumbers (
                        phoneNumbers: $phoneNumbers
                    ) {
                        __valid
                        phoneNumbers {
                            items {
                                __code
                                __msg
                                __path
                            }
                        }
                        __result
                    }
                }
            '),
            [
                'phoneNumbers' => [
                    [
                        'xxx-1234',
                        '555-12345',
                        '3'
                    ],
                    [
                        '555-1234',
                        '555-12345',
                        '343-4343',
                        'xxx-8309'
                    ]
                ],
            ],
            [
                '__valid' => false,
                '__result' => null,
                'phoneNumbers' => [
                    'items' => [
                        [
                            '__code' => 1,
                            '__msg' => "only hyphens and digits are allowed",
                            '__path' => [0, 0]
                        ],
                        [
                            '__code' => 1,
                            '__msg' => "only hyphens and digits are allowed",
                            '__path' => [1, 3]
                        ]
                    ]
                ]
            ]
        );
    }
}
