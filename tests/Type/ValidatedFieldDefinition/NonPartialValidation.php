<?php declare(strict_types=1);

namespace GraphQL\Tests\Type\ValidatedFieldDefinition;

use GraphQL\Tests\Type\FieldDefinition;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ValidatedFieldDefinition;

final class NonPartialValidation extends FieldDefinition
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
                        'partial' => false,
                        'type' => function () { // lazy load
                            return new InputObjectType([
                                'name' => 'BookAttributes',
                                'partial' => false,
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
                                                return [1, 'bar must be more than 10 characters'];
                                            }

                                            return 0;
                                        },
                                    ],

                                    'author' => [
                                        'type' => Type::id(),
                                        'description' => 'Provide a valid author id',
                                        'validate' => function (string $authorId) {
                                            if (! isset($this->data['people'][$authorId])) {
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
                    return ! $value;
                },
            ]),
            Utils::nowdoc('
                mutation UpdateBook(
                        $bookAttributes: BookAttributes
                    ) {
                    updateBook (
                        bookAttributes: $bookAttributes
                    ) {
                        valid
                        suberrors {
                            bookAttributes {
                                title {
                                    code
                                    msg
                                }
                                author {
                                    code
                                    msg
                                }
                                foo {
                                    code
                                    msg
                                }
                                bar {
                                    code
                                    msg
                                }
                            }
                        }
                        result
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
                'valid' => false,
                'suberrors' => [
                    'bookAttributes' => [
                        'title' => [
                            'code' => 1,
                            'msg' => 'book title must be less than 10 characters',
                        ],
                        'foo' => [
                            'code' => 1,
                            'msg' => 'foo is required',
                        ],
                        'bar' => [
                            'code' => 1,
                            'msg' => 'Oh, we absolutely must have a bar',
                        ],
                        'author' => [
                            'code' => 1,
                            'msg' => 'We have no record of that author',
                        ],
                    ],
                ],
                'result' => null,
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
                                        if (! isset($this->data['people'][$authorId])) {
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
                    return ! $value;
                },
            ]),
            Utils::nowdoc('
                mutation UpdateBook(
                        $bookAttributes: BookAttributes
                    ) {
                    updateBook (
                        bookAttributes: $bookAttributes
                    ) {
                        valid
                        suberrors {
                            bookAttributes {
                                suberrors {
                                    title {
                                        code
                                        msg
                                    }
                                    author {
                                        code
                                        msg
                                    }
                                }
                            }
                        }
                        result
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
                'valid' => false,
                'suberrors' => [
                    'bookAttributes' => [
                        'suberrors' => [
                            'title' => [
                                'code' => 1,
                                'msg' => 'book title must be less than 10 characters',
                            ],
                            'author' => [
                                'code' => 1,
                                'msg' => 'We have no record of that author',
                            ],
                        ],
                    ],
                ],
                'result' => null,
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
                        'validate' => static function () {
                        },
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
                    return ! $value;
                },
            ]),
            Utils::nowdoc('
                mutation UpdateBook(
                        $bookAttributes: [BookAttributes]
                    ) {
                    updateBook (
                        bookAttributes: $bookAttributes
                    ) {
                        valid
                        suberrors {
                            bookAttributes {
                                suberrors {
                                    title {
                                        code
                                        msg
                                    }
                                }
                            }
                        }
                        result
                    }
                }
            '),
            [
                'bookAttributes' => [[
                    'title' => 'The Catcher in the Rye',
                ]],
            ],
            [
                'valid' => false,
                'suberrors' => [
                    'bookAttributes' => [
                        [
                            'suberrors' => [
                                'title' => [
                                    'code' => 1,
                                    'msg' => 'book title must be less than 10 characters',
                                ],
                            ],
                        ],
                    ],
                ],
                'result' => null,
            ]
        );
    }
}
