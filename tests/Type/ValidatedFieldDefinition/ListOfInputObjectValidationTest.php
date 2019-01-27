<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
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

final class ListOfInputObjectValidationTest extends TestCase
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
                        'updateBooks' => new ValidatedFieldDefinition([
                            'name' => 'updateBooks',
                            'type' => Type::boolean(),
                            'args' => [
                                'bookAttributes' => [
                                    'type' => Type::listOf($this->bookAttributesInputType),
                                ],
                            ],
                            'resolve' => static function ($value, $args) : bool {
                                // ...
                                // do update
                                // ...

                                return true;
                            },
                        ]),
                    ];
                },
            ]),
        ]);
    }

    public function testValidationFail()
    {
        $res = GraphQL::executeQuery(
            $this->schema,
            Utils::nowdoc('
				mutation UpdateBooks(
                        $listOfBookAttributes: [BookAttributes]
                    ) {
                        updateBooks (
                            bookAttributes: $listOfBookAttributes
                        ) {
                            valid
                            suberrors {
                                bookAttributes {
                                    suberrors {
                                        index
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
                            }
                            result
                        }
                    }
			'),
            [],
            null,
            [
                'listOfBookAttributes' => [
                    [
                        'title' => 'Dogsbody',
                        'author' => 3,
                    ],
                    [
                        'title' => 'The Call of the Wild',
                        'author' => 5,
                    ],
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
                                        0 =>
                                            [
                                                'index' => 1,
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
                            ],
                    ],
                'result' => null,
            ],
            $res->data['updateBooks']
        );

        static::assertEmpty($res->errors);
        static::assertFalse($res->data['updateBooks']['valid']);
    }
}
