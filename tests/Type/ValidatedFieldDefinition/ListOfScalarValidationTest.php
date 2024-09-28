<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;
use GraphQlPhpValidationToolkit\Tests\Utils;
use PHPUnit\Framework\TestCase;

final class ListOfScalarValidationTest extends TestCase
{
    /** @var ObjectType */
    protected $query;

    /** @var Schema */
    protected $schema;

    protected function setUp(): void
    {
        $this->schema = new Schema([
            'query' => new ObjectType(['name' => 'Query', 'fields' => []]),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => static function () {
                    return [
                        'setPhoneNumbers' => new ValidatedFieldDefinition([
                            'name' => 'setPhoneNumbers',
                            'type' => Type::boolean(),
                            'validate' => static function (array $args) {
                                if (\count($args['phoneNumbers']) < 1) {
                                    return [1, 'You must submit at least one list of numbers'];
                                }

                                return 0;
                            },
                            'args' => [
                                'phoneNumbers' => [
                                    'type' => Type::listOf(Type::listOf(Type::string())),
                                    'validate' => static function ($phoneNumber) {
                                        $res = \preg_match('/^[0-9\-]+$/', $phoneNumber) === 1;

                                        return ! $res ? [1, 'That does not seem to be a valid phone number'] : 0;
                                    },
                                ],
                            ],
                            'resolve' => static function (array $phoneNumbers): bool {
                                return ! empty($phoneNumbers);
                            },
                        ]),
                    ];
                },
            ]),
        ]);
    }

    public function testItemsValidationOnWrappedTypeFail(): void
    {
        $res = GraphQL::executeQuery(
            $this->schema,
            Utils::nowdoc('
                mutation SetPhoneNumbers(
                        $phoneNumbers: [[String]]
                    ) {
                        setPhoneNumbers ( phoneNumbers: $phoneNumbers ) {
                            valid
                            suberrors {
                                phoneNumbers {
                                    path
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
            [
                'phoneNumbers' => [
                    [
                        '123-4567',
                        'xxx456-7890xxx',
                        '555-whoops',
                    ],
                ],
            ]
        );

        static::assertEquals(
            [
                'valid' => false,
                'suberrors' => [
                    'phoneNumbers' => [
                        [
                            'path' => [
                                0,
                                1,
                            ],
                            'code' => 1,
                            'msg' => 'That does not seem to be a valid phone number',
                        ],
                        [
                            'path' => [
                                0,
                                2,
                            ],
                            'code' => 1,
                            'msg' => 'That does not seem to be a valid phone number',
                        ],
                    ],
                ],
                'result' => null,
            ],
            $res->data['setPhoneNumbers']
        );

        static::assertEmpty($res->errors);
        static::assertFalse($res->data['setPhoneNumbers']['valid']);
    }

    public function testItemsValidationOnSelfFail(): void
    {
        $res = GraphQL::executeQuery(
            $this->schema,
            Utils::nowdoc('
                mutation SetPhoneNumbers(
                        $phoneNumbers: [[String]]
                    ) {
                        setPhoneNumbers ( phoneNumbers: $phoneNumbers ) {
                            valid
                            code
                            msg
                            suberrors {
                                phoneNumbers {
                                    code
                                    msg
                                    path
                                }
                            }
                            result
                        }
                    }
            '),
            [],
            null,
            [
                'phoneNumbers' => [],
            ]
        );

        static::assertEquals(
            [
                'valid' => false,
                'code' => 1,
                'msg' => 'You must submit at least one list of numbers',
                'suberrors' => null,
                'result' => null,
            ],
            $res->data['setPhoneNumbers']
        );

        static::assertEmpty($res->errors);
        static::assertFalse($res->data['setPhoneNumbers']['valid']);
    }

    public function testListOfValidationFail(): void
    {
        $res = GraphQL::executeQuery(
            $this->schema,
            Utils::nowdoc('
                mutation SetPhoneNumbers(
                        $phoneNumbers: [[String]]
                    ) {
                    setPhoneNumbers ( phoneNumbers: $phoneNumbers ) {
                        valid
                        suberrors {
                            phoneNumbers {
                                code
                                msg
                                path
                            }
                        }
                        result
                    }
                }
            '),
            [],
            null,
            [
                'phoneNumbers' => [
                    [],
                    [
                        '123-4567',
                        'xxx-7890',
                        '321-1234',
                    ],
                ],
            ]
        );

        static::assertEmpty($res->errors);
        static::assertEquals(
            [
                'valid' => false,
                'suberrors' => [
                    'phoneNumbers' => [
                        [
                            'code' => 1,
                            'msg' => 'That does not seem to be a valid phone number',
                            'path' => [
                                0 => 1,
                                1 => 1,
                            ],
                        ],
                    ],
                ],
                'result' => null,
            ],
            $res->data['setPhoneNumbers']
        );

        static::assertFalse($res->data['setPhoneNumbers']['valid']);
    }
}
