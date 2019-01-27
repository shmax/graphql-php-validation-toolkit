<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;

final class NonNullScalarValidationTest extends TestCase
{
    /** @var Type */
    protected $bookType;

    /** @var Type */
    protected $personType;

    /** @var mixed[] */
    protected $data = [
        'people' => [
            1 => ['firstName' => 'Wilson'],
        ],
        'books' => [
            1 => [
                'title' => 'Where the Red Fern Grows',
                'author' => 1,
            ],
        ],
    ];

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

        $this->bookType = new ObjectType([
            'name' => 'Book',
            'fields' => [
                'title' => [
                    'type' => Type::string(),
                    'resolve' => static function ($book) {
                        return $book['title'];
                    },
                ],
                'author' => [
                    'type' => $this->personType,
                    'resolve' => static function ($book) {
                        return $book['author'];
                    },
                ],
            ],
        ]);

        $this->schema = new Schema([
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => function () {
                    return [
                        'updateBook' => new ValidatedFieldDefinition([
                            'name' => 'updateBook',
                            'type' => $this->bookType,
                            'args' => [
                                'bookId' => [
                                    'type' => Type::nonNull(Type::id()),
                                    'errorCodes' => ['bookNotFound'],
                                    'validate' => function ($bookId) {
                                        if (isset($this->data['books'][$bookId])) {
                                            return 0;
                                        }

                                        return ['bookNotFound', 'Unknown book!'];
                                    },
                                ],
                            ],
                            'resolve' => function ($value, $args) : array {
                                return $this->data['books'][$args['bookId']];
                            },
                        ]),
                    ];
                },
            ]),
        ]);
    }

    public function testNonNullScalarValidationSuccess()
    {
        $res = GraphQL::executeQuery(
            $this->schema,
            Utils::nowdoc('
				mutation UpdateBook(
                        $bookId:ID!
                    ) {
                        updateBook (bookId: $bookId) {
                            valid
                            suberrors {
                                bookId {
                                    code
                                    msg
                                }
                            }
                            result {
                                title
                            }
                        }
                    }
			'),
            [],
            null,
            ['bookId' => 1]
        );

        static::assertTrue($res->data['updateBook']['valid']);
    }

    public function testNonNullScalarValidationFail()
    {
        $res = GraphQL::executeQuery(
            $this->schema,
            Utils::nowdoc('
				mutation UpdateBook(
                        $bookId:ID!
                    ) {
                        updateBook (bookId: $bookId) {
                            valid
                            suberrors {
                                bookId {
                                    code
                                    msg
                                }
                            }
                            result {
                                title
                            }
                        }
                    }
			'),
            [],
            null,
            ['bookId' => 37]
        );

        static::assertEmpty($res->errors);
        static::assertFalse($res->data['updateBook']['valid']);
    }
}
