<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidatedFieldDefinition;
use PHPUnit\Framework\TestCase;

final class ScalarValidationTest extends TestBase
{
    /** @var Type */
    protected $bookType;

    /** @var Type */
    protected $personType;

    /** @var mixed[] */
    protected $books = [
        1 => [
            'title' => 'Where the Red Fern Grows',
            'author' => 1,
        ],
    ];


    public function testNullableScalarValidationOnNullValueSuccess(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
                'name' => 'updateBook',
                'type' => new ObjectType([
                    'name' => 'Book',
                    'fields' => [
                        'title' => [
                            'type' => Type::string(),
                            'resolve' => static function ($book) {
                                return $book['title'];
                            },
                        ],
                        'author' => [
                            'type' => new ObjectType([
                                'name' => 'Person',
                                'fields' => [
                                    'firstName' => [
                                        'type' => Type::string(),
                                    ],
                                ],
                            ]),
                            'resolve' => static function ($book) {
                                return $book['author'];
                            },
                        ],
                    ],
                ]),
                'args' => [
                    'bookId' => [
                        'type' => Type::id(),
                        'validate' => function ($bookId) {
                            if (isset($this->books[$bookId])) {
                                return 0;
                            }

                            return [1, 'Unknown book!'];
                        },
                    ],
                ],
                'resolve' => static function ($value): bool {
                    return (bool)$value;
                },
            ]),
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
                        _result {
                            title
                        }
                    }
                }
            '),
            ['bookId' => null],
            [
                '_valid' => false,
                '_result' => null,
                'bookId' => [
                    '_code' => 1,
                    '_msg' => 'Unknown book!',
                ],
            ]
        );
    }
}
