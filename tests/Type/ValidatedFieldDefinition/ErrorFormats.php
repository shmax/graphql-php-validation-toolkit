<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidatedFieldDefinition;
use PHPUnit\Framework\TestCase;

enum BookError
{
    case required;
    case invalidIsbn;
}

final class ErrorFormats extends TestCase
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
                                return (bool)$value;
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
                        _valid
                        bookId {
                            _code
                            _msg
                        }
                        _result
                    }
                }
            '),
            [],
            null,
            ['bookId' => null]
        );

        static::assertEmpty($res->errors);
        static::assertEquals($res->data['updateBook']['bookId']['_code'], 1);
    }

    public function testEnumCodeType(): void
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
                                    'errorCodes' => BookError::class,
                                    'validate' => function ($bookId) {
                                        return empty($bookId) ? BookError::required : 0;
                                    },
                                ],
                            ],
                            'resolve' => static function ($value): bool {
                                return (bool)$value;
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
                        bookId {
                            _code
                            _msg
                        }
                        _valid
                        _result
                    }
                }
            '),
            [],
            null,
            ['bookId' => null]
        );

        static::assertEmpty($res->errors);
        static::assertEquals($res->data['updateBook']['bookId']['_code'], 'required');
    }

    public function testIntCodeTypeAndMessage(): void
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
                                return (bool)$value;
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
                        _valid
                        bookId {
                            _code
                            _msg
                        }
                        _result
                    }
                }
            '),
            [],
            null,
            ['bookId' => null]
        );

        static::assertEmpty($res->errors);
        static::assertEquals(1, $res->data['updateBook']['bookId']['_code']);
        static::assertEquals('Invalid Book Id', $res->data['updateBook']['bookId']['_msg']);
    }

    public function testEnumCodeTypeAndMessage(): void
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
                                    'errorCodes' => BookError::class,
                                    'validate' => function ($bookId) {
                                        return empty($bookId) ? [BookError::required, 'Invalid Book Id'] : 0;
                                    },
                                ],
                            ],
                            'resolve' => static function ($value): bool {
                                return (bool)$value;
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
                        _valid
                        bookId {
                            _code
                            _msg
                        }
                        _result
                    }
                }
            '),
            [],
            null,
            ['bookId' => null]
        );

        static::assertEmpty($res->errors);
        static::assertEquals('required', $res->data['updateBook']['bookId']['_code']);
        static::assertEquals('Invalid Book Id', $res->data['updateBook']['bookId']['_msg']);
    }
}
