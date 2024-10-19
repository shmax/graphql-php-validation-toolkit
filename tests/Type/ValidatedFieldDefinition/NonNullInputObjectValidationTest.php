<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidatedFieldDefinition;
use PHPUnit\Framework\TestCase;

final class NonNullInputObjectValidationTest extends TestBase
{
    protected array $data = [
        'people' => [
            1 => "Joe Blow",
            2 => "John Fonebone"
        ]
    ];

    public function testInputObjectValidationOnFieldFail(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
                'name' => 'updateBook',
                'type' => Type::boolean(),
                'args' => [
                    'attributes' => [
                        'type' => Type::nonNull(new InputObjectType([
                            'name' => 'bookAttributes',
                            'fields' => [
                                'title' => [
                                    'type' => Type::string(),
                                    'description' => 'Enter a book title, no more than 10 characters in length',
                                    'validate' => static function ($title) {
                                        if (\strlen($title) > 10) {
                                            return [1, 'book title must be less than 10 characters'];
                                        }

                                        return 0;
                                    },
                                ],
                                'author' => [
                                    'type' => Type::id(),
                                    'description' => 'Provide a valid author id',
                                    'validate' => function ($authorId) {
                                        if (!isset($this->data['people'][$authorId])) {
                                            return [1, 'We have no record of that author'];
                                        }

                                        return 0;
                                    },
                                ],
                            ]
                        ]))
                    ]
                ],
                'resolve' => static function ($value): bool {
                    return !$value;
                },
            ]),
            Utils::nowdoc('
                mutation UpdateBook($title: String, $author: ID) {
                    updateBook (
                        attributes: {author: $author, title: $title}
                    ) {
                        _valid
                        _result
                        attributes {
                            title {
                                _code
                                _msg
                            }
                            author {
                                _code
                                _msg
                            }
                        }
                    }
                }
            '),
            [
                'title' => 'The Catcher in the Rye',
                'author' => 4,
            ],
            [
                '_valid' => false,
                'attributes' => [
                    'title' => [
                        '_code' => 1,
                        '_msg' => 'book title must be less than 10 characters',
                    ],
                    'author' => [
                        '_code' => 1,
                        '_msg' => 'We have no record of that author',
                    ],
                ],
                '_result' => null,
            ]
        );
    }
}
