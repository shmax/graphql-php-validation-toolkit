<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidatedFieldDefinition;
use PHPUnit\Framework\TestCase;

final class GeneratedCodeTypeTest extends TestCase
{
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
                        fieldErrors {
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
        static::assertEquals($res->data['updateBook']['fieldErrors']['bookId']['code'], 1);
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
                                    'validate' => function ($bookId) {
                                        return empty($bookId) ? [1, 'Invalid Book Id'] : 0;
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
        static::assertEquals(1, $res->data['updateBook']['suberrors']['bookId']['code']);
        static::assertEquals('Invalid Book Id', $res->data['updateBook']['suberrors']['bookId']['msg']);
    }
}
