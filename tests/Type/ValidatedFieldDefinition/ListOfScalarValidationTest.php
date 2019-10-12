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

final class ListOfScalarValidationTest extends TestCase
{
    /** @var ObjectType */
    protected $query;

    /** @var Schema */
    protected $schema;

    protected function setUp(): void
    {
        $this->query  = new ObjectType(['name' => 'Query']);
        $this->schema = new Schema([
            'query' => $this->query,
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => static function () {
                    return [
                        'setPhoneNumbers' => new ValidatedFieldDefinition([
                            'name' => 'setPhoneNumbers',
                            'type' => Type::boolean(),
                            'errorCodes' => ['atLeastOneList'],
                            'validate' => static function (array $args) {
                                if (count($args['phoneNumbers']) < 1) {
                                    return ['atLeastOneList', 'You must submit at least one list of numbers'];
                                }
                                return 0;
                            },
                            'args' => [
                                'phoneNumbers' => [
                                    'type' => Type::listOf(Type::listOf(Type::string())),
                                    'errorCodes' => ['invalidPhoneNumber'],
                                    'validate' => static function ($phoneNumber) {
                                        $res = preg_match('/^[0-9\-]+$/', $phoneNumber) === 1;
                                        return !$res ? ['invalidPhoneNumber', 'That does not seem to be a valid phone number'] : 0;
                                    },
                                ],
                            ],
                            'resolve' => static function (array $phoneNumbers) : bool {
                                return !empty($phoneNumbers);
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
                    ],
                ],
            ]
        );

        static::assertEquals(
            array (
                'valid' => false,
                'suberrors' =>
                    [
                        'phoneNumbers' =>
                            [
                                [
                                    'path' =>
                                        [
                                            0,
                                            1,
                                        ],
                                    'code' => 'invalidPhoneNumber',
                                    'msg' => 'That does not seem to be a valid phone number',
                                ],
                            ],
                    ],
                'result' => null,
            ),
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
            array (
                'valid' => false,
                'code' => 'atLeastOneList',
                'msg' => 'You must submit at least one list of numbers',
                'suberrors' => null,
                'result' => null,
            ),
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
                'suberrors' =>
                    [
                        'phoneNumbers' =>
                            [
                                [
                                    'code' => 'invalidPhoneNumber',
                                    'msg' => 'That does not seem to be a valid phone number',
                                    'path' =>
                                        [
                                            0 => 1,
                                            1 => 1,
                                        ],
                                ],
                            ],
                    ],
                'result' => NULL,
            ],
            $res->data['setPhoneNumbers']
        );

        static::assertFalse($res->data['setPhoneNumbers']['valid']);
    }
}
