<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use function strlen;

final class InputObjectValidationTest extends TestCase
{
    /** @var Type */
    protected $bookType;

    /** @var InputObjectType */
    protected $bookAttributesInputType;

    /** @var Type */
    protected $personType;

    /** @var mixed[] */
    protected $data = [
        'people' => [
            1 => ['firstName' => 'Wilson'],
            2 => ['firstName' => 'J.D.'],
            3 => ['firstName' => 'Diana'],
        ],
    ];

    /** @var ObjectType */
    protected $query;

    /** @var Schema */
    protected $schema;

    protected function setUp()
    {
        $this->personType = new ObjectType([
            'name' => 'Person',
            'fields' => [
                'firstName' => [
                    'type' => Type::string(),
                    'phoneNumbers' => [
                        'type' => Type::listOf(Type::string()),
                    ],
                ],
            ],
        ]);

        $this->bookAttributesInputType = new InputObjectType([
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
                    'errorCodes' => [
                        'unknownAuthor',
                        'authorDeceased',
                    ],
                    'validate' => function (string $authorId) {
                        if (! isset($this->data['people'][$authorId])) {
                            return ['unknownAuthor', 'We have no record of that author'];
                        }
                        return 0;
                    },
                ],
            ],
        ]);

        $this->query = new ObjectType(['name' => 'Query']);

        $this->schema = new Schema([
            'query' => $this->query,
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => function () {
                    return [
                        'updateBook' => new ValidatedFieldDefinition([
                            'name' => 'updateBook',
                            'type' => Type::boolean(),
                            'args' => [
                                'bookAttributes' => [
                                    'type' => $this->bookAttributesInputType,
                                    'errorCodes' => ['titleOrIdRequired'],
                                    'validate' => static function (?array $bookAttributes) {
                                        if ($bookAttributes === null) {
                                            return 0;
                                        }

                                        return isset($bookAttributes['title']) || isset($bookAttributes['author']) ? 0 : [
                                            'titleOrIdRequired',
                                            'You must supply at least one of title or author',
                                        ];
                                    },
                                ],
                            ],
                            'resolve' => static function ($value, $args) : bool {
                                // ...
                                // do update
                                // ...

                                return ! $value;
                            },
                        ]),
                    ];
                },
            ]),
        ]);
    }

    public function testValidationInputObjectFieldFail()
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
                    'title' => 'The Catcher in the Rye',
                    'author' => 4,
                ],
            ]
        );

        static::assertEquals(
            [
                'valid' => false,
                'suberrors' =>
                    [
                        'bookAttributes' =>
                            [
                                'suberrors' =>
                                    [
                                        'title' =>
                                            [
                                                'code' => 1,
                                                'msg' => 'book title must be less than 10 chaacters',
                                            ],
                                        'author' =>
                                            [
                                                'code' => 'unknownAuthor',
                                                'msg' => 'We have no record of that author',
                                            ],
                                    ],
                            ],
                    ],
                'result' => null,
            ],
            $res->data['updateBook']
        );

        static::assertFalse($res->data['updateBook']['valid']);
    }

    public function testValidationInputObjectSelfFail()
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
            [
                'valid' => false,
                'suberrors' =>
                    [
                        'bookAttributes' =>
                            [
                                'code' => 'titleOrIdRequired',
                                'msg' => 'You must supply at least one of title or author',
                                'suberrors' => null,
                            ],
                    ],
                'result' => null,
            ],
            $res->data['updateBook']
        );

        static::assertFalse($res->data['updateBook']['valid']);
    }

    public function testValidationSuccess()
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

    public function testValidationEmptyInput()
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
