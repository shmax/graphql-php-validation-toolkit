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
                        _valid
                        bookAttributes {
                            foo {
                                _code
                                _msg
                            }
                            bar {
                                _code
                                _msg
                            }
                            naz {
                                _code
                                _msg
                            }
                            dingus {
                                _code
                                _msg
                            }
                            gadgets {
                                _code
                                _msg
                            }
                        }
                        _result
                    }
                }
            '),
            [
                'bookAttributes' => [
                ],
            ],
            [
                '_valid' => false,
                'bookAttributes' => [
                    'foo' => [
                        '_code' => 1,
                        '_msg' => 'foo is required',
                    ],
                    'bar' => [
                        '_code' => 1,
                        '_msg' => 'Oh, we absolutely must have a bar',
                    ],
                    'naz' => [
                        '_code' => 1,
                        '_msg' => 'naz is required',
                    ],
                    'dingus' => [
                        '_code' => 'dingusRequired',
                        '_msg' => 'Make with the dingus',
                    ],
                    'gadgets' => [
                        '_code' => 1,
                        '_msg' => 'gadgets is required',
                    ],
                ],
                '_result' => null,
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
                                    // empty string
                                    'foo' => [
                                        'type' => Type::string(),
                                        'description' => 'Provide a foo',
                                        'required' => true,
                                    ],

                                    // empty id
                                    'doodad' => [
                                        'type' => Type::id(),
                                        'description' => 'Provide a doodad',
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
                        _valid
                        bookAttributes {
                            foo {
                                _code
                                _msg
                            }
                            doodad {
                                _code
                                _msg
                            }
                            bar {
                                _code
                                _msg
                            }
                            naz {
                                _code
                                _msg
                            }
                            dingus {
                                _code
                                _msg
                            }
                            gadgets {
                                _code
                                _msg
                            }
                        }
                        _result
                    }
                }
            '),
            [
                'bookAttributes' => [
                    'foo' => '',
                    'doodad' => '',
                    'bar' => null,
                    'naz' => '',
                    'dingus' => '',
                    'gadgets' => []
                ],
            ],
            [
                '_valid' => false,
                'bookAttributes' => [
                    'foo' => [
                        '_code' => 1,
                        '_msg' => 'foo is required',
                    ],
                    'doodad' => [
                        '_code' => 1,
                        '_msg' => 'doodad is required',
                    ],
                    'bar' => [
                        '_code' => 1,
                        '_msg' => 'Oh, we absolutely must have a bar',
                    ],
                    'naz' => [
                        '_code' => 1,
                        '_msg' => 'naz is required',
                    ],
                    'dingus' => [
                        '_code' => 'dingusRequired',
                        '_msg' => 'Make with the dingus',
                    ],
                    'gadgets' => [
                        '_code' => 1,
                        '_msg' => 'gadgets is required',
                    ],
                ],
                '_result' => null,
            ]
        );
    }
}
