<?php declare(strict_types=1);

namespace GraphQL\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;

final class GeneratedCodeTypeTest extends TestCase
{
    /** @var Type */
    public function testIntCodeType(): void
    {
        $schema = new Schema([
            'query' => new ObjectType(['name' => 'Query', 'fields' => []]),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => function () {
                    return [
                        'updateBook' => new ValidatedFieldDefinition([
                            'name' => 'updateBook',
                            'type' => Type::boolean(),
                            'args' => [
                                'bookId' => [
                                    'type' => Type::id(),
                                    'validate' => function ($bookId) {
                                        return empty($bookId) ? 1 : 0;
                                    },
                                ],
                            ],
                            'resolve' => static function ($value): bool {
                                return (bool) $value;
                            },
                        ]),
                    ];
                },
            ]),
        ]);

        $res = GraphQL::executeQuery(
            $schema,
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
                        result
                    }
                }
            '),
            [],
            null,
            ['bookId' => null]
        );

        static::assertEmpty($res->errors);
        static::assertEquals($res->data['updateBook']['suberrors']['bookId']['code'], 1);
    }

    public function testStringCodeType(): void
    {
        $schema = new Schema([
            'query' => new ObjectType(['name' => 'Query', 'fields' => []]),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => function () {
                    return [
                        'updateBook' => new ValidatedFieldDefinition([
                            'name' => 'updateBook',
                            'type' => Type::boolean(),
                            'args' => [
                                'bookId' => [
                                    'type' => Type::id(),
                                    'errorCodes' => [
                                        'invalidBookId',
                                    ],
                                    'validate' => function ($bookId) {
                                        return empty($bookId) ? ['invalidBookId', 'Invalid Book Id'] : 0;
                                    },
                                ],
                            ],
                            'resolve' => static function ($value): bool {
                                return (bool) $value;
                            },
                        ]),
                    ];
                },
            ]),
        ]);

        $res = GraphQL::executeQuery(
            $schema,
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
                        result
                    }
                }
            '),
            [],
            null,
            ['bookId' => null]
        );

        static::assertEmpty($res->errors);
        static::assertEquals('invalidBookId', $res->data['updateBook']['suberrors']['bookId']['code']);
        static::assertEquals('Invalid Book Id', $res->data['updateBook']['suberrors']['bookId']['msg']);
    }
}
