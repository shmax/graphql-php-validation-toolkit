<?php declare(strict_types=1);


use GraphQL\Tests\Type\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidatedFieldDefinition;

enum DingusError
{
    case dingusRequired;
}

final class RequiredFieldsValidation extends TestBase
{
    /** @var mixed[] */
    protected $data = [
        'people' => [
            1 => ['firstName' => 'Wilson'],
            2 => ['firstName' => 'J.D.'],
            3 => ['firstName' => 'Diana'],
        ],
    ];

    public function testRequiredFailByOmission(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
                'name' => 'updateBook',
                'type' => Type::boolean(),
                'args' => [
                    'bookAttributes' => [
                        'type' => function () { // lazy load
                            return new InputObjectType([
                                'name' => 'BookAttributes',
                                'fields' => [
                                    // basic required functionality
                                    'foo' => [
                                        'type' => Type::string(),
                                        'description' => 'Provide a foo',
                                        'required' => true,
                                    ],

                                    // custom required response (with [int, string])
                                    'bar' => [
                                        'type' => Type::string(),
                                        'description' => 'Provide a bar',
                                        'required' => [1, 'Oh, we absolutely must have a bar'],
                                    ],

                                    // required callback
                                    'naz' => [
                                        'type' => Type::string(),
                                        'description' => 'Provide a naz',
                                        'required' => static fn() => true,
                                    ],

                                    // custom required response (with [enum, string])
                                    'dingus' => [
                                        'type' => Type::string(),
                                        'errorCodes' => DingusError::class,
                                        'description' => 'Provide a bar',
                                        'required' => [DingusError::dingusRequired, 'Make with the dingus'],
                                    ],

                                    // list of scalar
                                    'gadgets' => [
                                        'type' => Type::listOf(Type::string()),
                                        'required' => true,
                                    ],
                                ],
                            ]);
                        },
                    ],
                ],
                'resolve' => static function ($value): bool {
                    return !$value;
                },
            ]),
            Utils::nowdoc('
                mutation UpdateBook(
                        $bookAttributes: BookAttributes
                    ) {
                    updateBook (
                        bookAttributes: $bookAttributes
                    ) {
                        __valid
                        bookAttributes {
                            foo {
                                __code
                                __msg
                            }
                            bar {
                                __code
                                __msg
                            }
                            naz {
                                __code
                                __msg
                            }
                            dingus {
                                __code
                                __msg
                            }
                            gadgets {
                                __code
                                __msg
                            }
                        }
                        __result
                    }
                }
            '),
            [
                'bookAttributes' => [
                ],
            ],
            [
                '__valid' => false,
                'bookAttributes' => [
                    'foo' => [
                        '__code' => 1,
                        '__msg' => 'foo is required',
                    ],
                    'bar' => [
                        '__code' => 1,
                        '__msg' => 'Oh, we absolutely must have a bar',
                    ],
                    'naz' => [
                        '__code' => 1,
                        '__msg' => 'naz is required',
                    ],
                    'dingus' => [
                        '__code' => 'dingusRequired',
                        '__msg' => 'Make with the dingus',
                    ],
                    'gadgets' => [
                        '__code' => 1,
                        '__msg' => 'gadgets is required',
                    ],
                ],
                '__result' => null,
            ]
        );
    }

    public function testRequiredFailByEmptyValue(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
                'name' => 'updateBook',
                'type' => Type::boolean(),
                'args' => [
                    'bookAttributes' => [
                        'type' => function () { // lazy load
                            return new InputObjectType([
                                'name' => 'BookAttributes',
                                'fields' => [
                                    // basic required functionality
                                    'foo' => [
                                        'type' => Type::string(),
                                        'description' => 'Provide a foo',
                                        'required' => true,
                                    ],

                                    // custom required response (with [int, string])
                                    'bar' => [
                                        'type' => Type::string(),
                                        'description' => 'Provide a bar',
                                        'required' => [1, 'Oh, we absolutely must have a bar'],
                                    ],

                                    // required callback
                                    'naz' => [
                                        'type' => Type::string(),
                                        'description' => 'Provide a naz',
                                        'required' => static fn() => true,
                                    ],

                                    // custom required response (with [enum, string])
                                    'dingus' => [
                                        'type' => Type::string(),
                                        'errorCodes' => DingusError::class,
                                        'description' => 'Provide a bar',
                                        'required' => [DingusError::dingusRequired, 'Make with the dingus'],
                                    ],

                                    // list of scalar
                                    'gadgets' => [
                                        'type' => Type::listOf(Type::string()),
                                        'required' => true,
                                    ],
                                ],
                            ]);
                        },
                    ],
                ],
                'resolve' => static function ($value): bool {
                    return !$value;
                },
            ]),
            Utils::nowdoc('
                mutation UpdateBook(
                        $bookAttributes: BookAttributes
                    ) {
                    updateBook (
                        bookAttributes: $bookAttributes
                    ) {
                        __valid
                        bookAttributes {
                            foo {
                                __code
                                __msg
                            }
                            bar {
                                __code
                                __msg
                            }
                            naz {
                                __code
                                __msg
                            }
                            dingus {
                                __code
                                __msg
                            }
                            gadgets {
                                __code
                                __msg
                            }
                        }
                        __result
                    }
                }
            '),
            [
                'bookAttributes' => [
                    'foo' => '',
                    'bar' => null,
                    'naz' => '',
                    'dingus' => '',
                    'gadgets' => []
                ],
            ],
            [
                '__valid' => false,
                'bookAttributes' => [
                    'foo' => [
                        '__code' => 1,
                        '__msg' => 'foo is required',
                    ],
                    'bar' => [
                        '__code' => 1,
                        '__msg' => 'Oh, we absolutely must have a bar',
                    ],
                    'naz' => [
                        '__code' => 1,
                        '__msg' => 'naz is required',
                    ],
                    'dingus' => [
                        '__code' => 'dingusRequired',
                        '__msg' => 'Make with the dingus',
                    ],
                    'gadgets' => [
                        '__code' => 1,
                        '__msg' => 'gadgets is required',
                    ],
                ],
                '__result' => null,
            ]
        );
    }
}
