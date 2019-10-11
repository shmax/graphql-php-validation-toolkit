<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Tests\Type\FieldDefinitionTest;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use function strlen;

final class InputObjectValidationTest extends FieldDefinitionTest
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
                        'type' => new InputObjectType([
                            'name' => 'BookAttributes',
                            'fields' => [
                                'title' => [
                                    'type' => Type::string(),
                                    'description' => 'Enter a book title, no more than 10 characters in length',
                                    'validate' => static function (string $title) {
                                        if (strlen($title) > 10) {
                                            return [1, 'book title must be less than 10 chaacters'];
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
                'suberrors' =>
                    [
                        'bookAttributes' =>
                            [
                                'title' =>
                                    [
                                        'code' => 1,
                                        'msg' => 'book title must be less than 10 chaacters',
                                    ],
                                'author' =>
                                    [
                                        'code' => 1,
                                        'msg' => 'We have no record of that author',
                                    ],
                            ],
                    ],
                'result' => null,
            ]
        );
    }

    public function testValidationInputObjectSelfFail(): void
    {
        $res = GraphQL::executeQuery(
            $this->schema,
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
                                code
                                msg
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
            [],
            null,
            [
                'bookAttributes' => [
                    'title' => null,
                    'author' => null,
                ],
            ]
        );

        static::assertEquals(
            array(
                'valid' => false,
                'suberrors' =>
                    [
                        'bookAttributes' =>
                            [
                                'code' => 'titleOrIdRequired',
                                'msg' => 'You must supply at least one of title or author',
                                'suberrors' => NULL,
                            ],
                    ],
                'result' => null,
            ),
            $res->data['updateBook']
        );

        static::assertFalse($res->data['updateBook']['valid']);
    }

    public function testValidationSuccess(): void
    {
        $res = GraphQL::executeQuery(
            $this->schema,
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
            [],
            null,
            [
                'bookAttributes' => [
                    'title' => 'Dogsbody',
                    'author' => 3,
                ],
            ]
        );

        static::assertEmpty($res->errors);
        static::assertEquals(
            [
                'valid' => true,
                'suberrors' => null,
                'result' => true,
            ],
            $res->data['updateBook']
        );

        static::assertTrue($res->data['updateBook']['valid']);
    }

    public function testValidationEmptyInput(): void
    {
        $res = GraphQL::executeQuery(
            $this->schema,
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
            [],
            null,
            ['bookAttributes' => null]
        );

        static::assertEmpty($res->errors);
        static::assertEquals(
            [
                'valid' => true,
                'suberrors' => null,
                'result' => true,
            ],
            $res->data['updateBook']
        );

        static::assertTrue($res->data['updateBook']['valid']);
    }
}
