<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Tests\Type\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidatedFieldDefinition;

final class ListOfInputObjectValidation extends TestBase
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

    public function testValidationFail(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
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
                                            return [1, 'book title must be less than 10 characters'];
                                        }

                                        return 0;
                                    },
                                ],
                                'author' => [
                                    'type' => Type::id(),
                                    'description' => 'Provide a valid author id',
                                    'validate' => function (string $authorId) {
                                        if (!isset($this->data['people'][$authorId])) {
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
                    return (bool)$value;
                },
            ]),
            Utils::nowdoc('
                mutation UpdateBooks(
                        $bookAttributes: [BookAttributes]
                    ) {
                    updateBooks (
                        bookAttributes: $bookAttributes
                    ) {
                        __valid
                        __result
                        bookAttributes {
                            title {
                                __code
                                __msg
                            }
                            __path
                        }
                    }
                }
            '),
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
            ],
            [
                '__valid' => true,
                '__result' => null,
                'phoneNumbers' => null
            ]
        );
    }
}
