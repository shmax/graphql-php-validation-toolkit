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

    protected function setUp()
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
                            'args' => [
                                'phoneNumbers' => [
                                    'type' => Type::listOf(Type::string()),
                                    'errorCodes' => ['maxNumExceeded'],
                                    'validate' => static function (array $phoneNumbers) {
                                        if (count($phoneNumbers) > 2) {
                                            return ['maxNumExceeded', 'You may not submit more than 2 phone numbers'];
                                        }
                                        return 0;
                                    },
                                    'suberrorCodes' => ['invalidPhoneNumber'],
                                    'validateItem' => static function ($phoneNumber) {
                                        $res = preg_match('/^[0-9\-]+$/', $phoneNumber) === 1;
                                        return ! $res ? ['invalidPhoneNumber', 'That does not seem to be a valid phone number'] : 0;
                                    },
                                ],
                            ],
                            'resolve' => static function (array $phoneNumbers, $args) : bool {
                                // ...
                                // stash them somewhere
                                // ...

                                return true;
                            },
                        ]),
                    ];
                },
            ]),
        ]);
    }

    public function testItemsValidationFail()
    {
        $res = GraphQL::executeQuery(
            $this->schema,
            Utils::nowdoc('
				mutation SetPhoneNumbers(
                        $phoneNumbers: [String]
                    ) {
                        setPhoneNumbers ( phoneNumbers: $phoneNumbers ) {
                            valid
                            suberrors {
                                phoneNumbers {
                                    suberrors {
                                        index
                                        code
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
                'phoneNumbers' => [
                    '123-4567',
                    'xxx456-7890xxx',
                ],
            ]
        );

        static::assertEquals(
            [
                'valid' => false,
                'suberrors' =>
                    [
                        'phoneNumbers' =>
                            [
                                'suberrors' =>
                                    [
                                        0 =>
                                            [
                                                'index' => 1,
                                                'code' => 'invalidPhoneNumber',
                                            ],
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

    public function testListOfValidationFail()
    {
        $res = GraphQL::executeQuery(
            $this->schema,
            Utils::nowdoc('
				mutation SetPhoneNumbers(
                        $phoneNumbers: [String]
                    ) {
                        setPhoneNumbers ( phoneNumbers: $phoneNumbers ) {
                            valid
                            suberrors {
                                phoneNumbers {
                                    code
                                    msg
                                    suberrors {
                                        index
                                        code
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
                'phoneNumbers' => [
                    '123-4567',
                    '456-7890',
                    '321-1234',
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
                                'code' => 'maxNumExceeded',
                                'msg' => 'You may not submit more than 2 phone numbers',
                                'suberrors' => null,
                            ],
                    ],
                'result' => null,
            ],
            $res->data['setPhoneNumbers']
        );

        static::assertFalse($res->data['setPhoneNumbers']['valid']);
    }
}
