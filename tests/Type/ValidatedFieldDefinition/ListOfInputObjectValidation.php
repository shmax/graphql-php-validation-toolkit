<?php declare(strict_types=1);

namespace GraphQL\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Tests\Type\FieldDefinition;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;

final class ListOfInputObjectValidation extends FieldDefinition
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
            'query' => new ObjectType(['name' => 'Query', 'fields' => []]),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => function () {
                    return [
                        'updateBooks' => new ValidatedFieldDefinition([
                            'name' => 'updateBooks',
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
                                                        return [1, 'book title must be less than 10 chaacters'];
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
                                    ])),
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
                        suberrors {
                            bookAttributes {
                                suberrors {
                                    title {
                                        code
                                        msg
                                    }
                                }
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
            [
                'valid' => false,
                'result' => null,
                'suberrors' => [
                    'bookAttributes' => [
                        [
                            'suberrors' => [
                                'title' => null,
                            ],
                            'path' => [1],
                        ],
                    ],
                ],
            ],
            $res->data['updateBooks']
        );

        static::assertFalse($res->data['updateBooks']['valid']);
    }
}
