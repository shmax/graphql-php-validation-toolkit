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

final class ScalarValidationTest extends TestCase
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

    /** @var ObjectType */
    protected $query;

    /** @var Schema */
    protected $schema;

    protected function setUp(): void
    {
        $this->personType = new ObjectType([
            'name' => 'Person',
            'fields' => [
                'firstName' => [
                    'type' => Type::string(),
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

        $this->query = new ObjectType(['name' => 'Query']);

        $this->schema = new Schema([
            'query' => $this->query,
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => function () {
                    return [
                        'updateBook' => new ValidatedFieldDefinition([
                            'name' => 'updateBook',
                            'type' => $this->bookType,
                            'args' => [
                                'bookId' => [
                                    'type' => Type::id(),
                                    'errorCodes' => ['bookNotFound'],
                                    'validate' => function ($bookId) {
                                        if (isset($this->data['books'][$bookId])) {
                                            return 0;
                                        }

                                        return ['bookNotFound', 'Unknown book!'];
                                    },
                                ],
                            ],
                            'resolve' => static function ($value) : bool {
                                return !!$value;
                            },
                        ]),
                    ];
                },
            ]),
        ]);
    }

    public function testNullableScalarValidationOnNullValueSuccess(): void
    {
        $res = GraphQL::executeQuery(
            $this->schema,
            Utils::nowdoc('
                mutation UpdateBook(
                    $bookId:ID
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
            ['bookId' => null]
        );

        static::assertEmpty($res->errors);
        static::assertTrue($res->data['updateBook']['valid']);
    }
}
