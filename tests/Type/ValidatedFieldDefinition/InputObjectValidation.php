<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ValidatedFieldDefinition;

use GraphQL\Tests\Type\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidatedFieldDefinition;

final class InputObjectValidation extends TestBase
{
    /** @var mixed[] */
    protected $data = [
        'people' => [
            1 => ['firstName' => 'Wilson'],
            2 => ['firstName' => 'J.D.'],
            3 => ['firstName' => 'Diana'],
        ],
    ];

    public function testInputObjectValidationOnFieldFail(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
                'name' => 'updateBook',
                'type' => Type::boolean(),
                'args' => [
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
                'resolve' => static function ($value): bool {
                    return !$value;
                },
            ]),
            Utils::nowdoc('
                mutation UpdateBook($title: String, $author: ID) {
                    updateBook (
                        author: $author, title: $title
                    ) {
                        _valid
                        title {
                            _code
                            _msg
                        }
                        author {
                            _code
                            _msg
                        }
                        _result
                    }
                }
            '),
            [
                'title' => 'The Catcher in the Rye',
                'author' => 4,
            ],
            [
                '_valid' => false,
                'title' => [
                    '_code' => 1,
                    '_msg' => 'book title must be less than 10 characters',
                ],
                'author' => [
                    '_code' => 1,
                    '_msg' => 'We have no record of that author',
                ],
                '_result' => null,
            ]
        );
    }

    public function testInputObjectValidationOnSelfFail(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
                'name' => 'updateBook',
                'type' => Type::boolean(),
                'validate' => static function ($info) {
                    if (!empty($info['title']) && empty($info['author'])) {
                        return [1, "If title is set, then author is required"];
                    }
                    return 0;
                },
                'args' => [
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
                'resolve' => static function ($value): bool {
                    return !$value;
                },
            ]),
            Utils::nowdoc('
                mutation UpdateBook($title: String, $author: ID) {
                    updateBook (
                        author: $author, title: $title
                    ) {
                        _valid
                        _code
                        _msg
                        title {
                            _code
                            _msg
                        }
                        author {
                            _code
                            _msg
                        }
                        _result
                    }
                }
            '),
            [
                'title' => 'The Catcher in the Rye',
                'author' => '',
            ],
            [
                '_valid' => false,
                '_code' => 1,
                '_msg' => "If title is set, then author is required",
                'title' => [
                    '_code' => 1,
                    '_msg' => 'book title must be less than 10 characters',
                ],
                'author' => [
                    '_code' => 1,
                    '_msg' => 'We have no record of that author',
                ],
                '_result' => null,
            ]
        );
    }
}
