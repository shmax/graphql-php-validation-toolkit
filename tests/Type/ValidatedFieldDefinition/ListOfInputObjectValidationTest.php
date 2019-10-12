<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Tests\Type\FieldDefinitionTest;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use function count;
use function preg_match;
use function strlen;

final class ListOfInputObjectValidationTest extends FieldDefinitionTest
{
    /** @var mixed[] */
    protected $data = [
        'people' => [
            1 => ['firstName' => 'Wilson'],
            2 => ['firstName' => 'J.D.'],
            3 => ['firstName' => 'Diana'],
        ],
    ];

    /** @var Schema */
    protected $schema;

    protected function setUp(): void
    {
        $this->schema = new Schema([
            'query' => new ObjectType(['name' => 'Query']),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => function () {
                    return [
                        'updateBooks' => new ValidatedFieldDefinition([
                            'name' => 'updateBooks',
                            'type' => Type::boolean(),
                            'validate' => static function ($book) {
                                return !empty($book['author']) || !empty($book['title']) ? 0: [1, 'You must set an author or a title'];
                            },
                            'args' => [
                                'bookAttributes' => [
                                    'type' => Type::listOf(new InputObjectType([
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
                                                    if (!isset($this->data['people'][$authorId])) {
                                                        return ['unknownAuthor', 'We have no record of that author'];
                                                    }
                                                    return 0;
                                                },
                                            ],
                                        ],
                                    ])),
                                    'validate' => static function ($var) {
                                        return $var ? 0: 1;
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

    public function testValidationFail(): void
    {
        $res = GraphQL::executeQuery(
            $this->schema,
            Utils::nowdoc('
                mutation UpdateBooks(
                        $bookAttributes: [BookAttributes]
                    ) {
                    updateBooks (
                        bookAttributes: $bookAttributes
                    ) {
                        valid
                        result
                        code
                        msg
                        suberrors {
                            bookAttributes {
                                code
                                msg
                                path
                            }
                        }
                    }
                }
            '),
            [],
            null,
            [
                'bookAttributes' => [
                    [
                        'title' => 'Dogsbody',
                        'author' => 3,
                    ],
                    [
                        'title' => '',
                        'author' => '',
                    ],
                ],
            ]
        );

        static::assertEmpty($res->errors);

        static::assertEquals(
            array (
                'valid' => false,
                'result' => null,
                'code' => 1,
                'msg' => 'You must set an author or a title',
                'suberrors' => null,
            ),
            $res->data['updateBooks']
        );

        static::assertFalse($res->data['updateBooks']['valid']);
    }
}
