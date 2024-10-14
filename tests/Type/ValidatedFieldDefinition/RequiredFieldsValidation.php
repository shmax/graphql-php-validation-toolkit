<?php declare(strict_types=1);


use GraphQL\Tests\Type\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidatedFieldDefinition;

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

    public function testInputObjectValidationOnFieldFail(): void
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
                                    'title' => [
                                        'type' => Type::string(),
                                        'description' => 'Enter a book title, no more than 10 characters in length',
                                        'validate' => static function (string $title) {
                                            if (\strlen($title) > 10) {
                                                return [1, 'book title must be less than 10 characters'];
                                            }

                                            return 0;
                                        },
                                    ],
                                    'foo' => [
                                        'type' => Type::string(),
                                        'description' => 'Provide a foo',
                                        'required' => true,
                                        'validate' => function (string $foo) {
                                            if (strlen($foo) < 10) {
                                                return [1, 'foo must be more than 10 characters'];
                                            }

                                            return 0;
                                        },
                                    ],
                                    'bar' => [
                                        'type' => Type::string(),
                                        'description' => 'Provide a bar',
                                        'required' => [1, 'Oh, we absolutely must have a bar'],
                                        'validate' => function (string $foo) {
                                            if (strlen($foo) < 10) {
                                                return [1, 'bar must be more than 10 characters!'];
                                            }

                                            return 0;
                                        },
                                    ],
                                    'naz' => [
                                        'type' => Type::string(),
                                        'description' => 'Provide a naz',
                                        'required' => static fn() => true,
                                        'validate' => function (string $naz) {
                                            if (strlen($naz) < 10) {
                                                return [1, 'naz must be more than 10 characters!'];
                                            }

                                            return 0;
                                        }
                                    ],
                                    'author' => [
                                        'type' => Type::id(),
                                        'description' => 'Provide a valid author id',
                                        'validate' => function (string $authorId) {
                                            if (!isset($this->data['people'][$authorId])) {
                                                return [1, 'We have no record of that author'];
                                            }

                                            return 0;
                                        },
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
                            title {
                                __code
                                __msg
                            }
                            author {
                                __code
                                __msg
                            }
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
                        }
                        __result
                    }
                }
            '),
            [
                'bookAttributes' => [
                    'title' => 'The Catcher in the Rye',
                    'author' => 4,
                ],
            ],
            [
                '__valid' => false,
                'bookAttributes' => [
                    'title' => [
                        '__code' => 1,
                        '__msg' => 'book title must be less than 10 characters',
                    ],
                    'foo' => [
                        '__code' => 1,
                        '__msg' => 'foo is required',
                    ],
                    'bar' => [
                        '__code' => 1,
                        '__msg' => 'Oh, we absolutely must have a bar',
                    ],
                    'author' => [
                        '__code' => 1,
                        '__msg' => 'We have no record of that author',
                    ],
                    'naz' => [
                        '__code' => 1,
                        '__msg' => 'naz is required',
                    ],
                ],
                '__result' => null,
            ]
        );
    }

    public function testInputObjectSuberrorsValidationOnSelf(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
                'name' => 'updateBook',
                'type' => Type::boolean(),
                'args' => [
                    'bookAttributes' => [
                        'validate' => static function ($atts) {
                            return 0;
                        },
                        'type' => new InputObjectType([
                            'name' => 'BookAttributes',
                            'fields' => [
                                'title' => [
                                    'type' => Type::string(),
                                    'description' => 'Enter a book title, no more than 10 characters in length',
                                    'validate' => static function (string $title) {
                                        if (\strlen($title) > 10) {
                                            return [1, 'book title must be less than 10 characters'];
                                        }

                                        return 0;
                                    },
                                ],
                                'author' => [
                                    'type' => Type::id(),
                                    'description' => 'Provide a valid author id',
                                    'validate' => function (string $authorId) {
                                        if (!isset($this->data['people'][$authorId])) {
                                            return [1, 'We have no record of that author'];
                                        }

                                        return 0;
                                    },
                                ],
                            ],
                        ]),
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
                            title {
                                __code
                                __msg
                            }
                            author {
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
                    'title' => 'The Catcher in the Rye',
                    'author' => 4,
                ],
            ],
            [
                '__valid' => false,
                'bookAttributes' => [
                    'title' => [
                        '__code' => 1,
                        '__msg' => 'book title must be less than 10 characters',
                    ],
                    'author' => [
                        '__code' => 1,
                        '__msg' => 'We have no record of that author',
                    ],
                ],
                '__result' => null,
            ]
        );
    }

    public function testListOfInputObjectSuberrorsValidationOnChildField(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
                'name' => 'updateBook',
                'type' => Type::boolean(),
                'args' => [
                    'bookAttributes' => [
                        'type' => Type::listOf(new InputObjectType([
                            'name' => 'BookAttributes',
                            'fields' => [
                                'title' => [
                                    'type' => Type::string(),
                                    'description' => 'Enter a book title, no more than 10 characters in length',
                                    'validate' => static function (string $title) {
                                        if (\strlen($title) > 10) {
                                            return [1, 'book title must be less than 10 characters'];
                                        }

                                        return 0;
                                    },
                                ],
                            ],
                        ])),
                    ],
                ],
                'resolve' => static function ($value): bool {
                    return !$value;
                },
            ]),
            Utils::nowdoc('
                mutation UpdateBook(
                        $bookAttributes: [BookAttributes]
                    ) {
                    updateBook (
                        bookAttributes: $bookAttributes
                    ) {
                        __valid
                        __result
                        bookAttributes {
                            items {
                                title {
                                    __code
                                    __msg
                                }
                            }
                        }
                    }
                }
            '),
            [
                'bookAttributes' => [[
                    'title' => 'The Catcher in the Rye',
                ]],
            ],
            [
                '__valid' => false,
                'bookAttributes' => [
                    'items' => [
                        [
                            'title' => [
                                '__code' => 1,
                                '__msg' => 'book title must be less than 10 characters',
                            ]
                        ]
                    ]
                ],
                '__result' => null,
            ]
        );
    }
}
