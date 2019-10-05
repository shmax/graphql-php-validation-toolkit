<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;
use function array_keys;

final class InputObjectTest extends TestCase
{
    public function testFieldsWithNoErrorCodes()
    {
        $type = new UserErrorsType([
            'type' => new InputObjectType([
                'name' => 'bookInput',
                'fields' => [
                    'authorId' => [
                        'type' => Type::id(),
                        'description' => 'An author Id',
                    ],
                ],
            ]),
        ], ['updateBook']);

        self::assertEquals(
            Utils::nowdoc('
                schema {
                  query: UpdateBookError
                }
                
                """User errors for UpdateBook"""
                type UpdateBookError {
                
                }

            '),
            SchemaPrinter::doPrint(new Schema(['query' => $type]))
        );
    }

    public function testFieldsWithErrorCodesButNoValidate()
    {
        $this->expectExceptionMessage('If you specify errorCodes, you must also provide a validate callback');

        new UserErrorsType([
            'type' => new InputObjectType([
                'name' => 'bookInput',
                'fields' => [
                    'authorId' => [
                        'errorCodes' => ['unknownAuthor'],
                        'type' => Type::id(),
                        'description' => 'An author Id',
                    ],
                ],
            ]),
        ], ['updateBook']);
    }

    public function testFieldsWithValidate()
    {
        $type = new UserErrorsType([
            'type' => new InputObjectType([
                'name' => 'bookInput',
                'fields' => [
                    'authorId' => [
                        'errorCodes' => ['unknownAuthor'],
                        'validate' => static function (int $authorId) {
                            return $authorId ? 0 : 1;
                        },
                        'type' => Type::id(),
                        'description' => 'An author Id',
                    ],
                ],
            ]),
        ], ['updateBook']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: UpdateBookError
            }
            
            """User errors for UpdateBook"""
            type UpdateBookError {
              """Suberrors for UpdateBook"""
              suberrors: UpdateBook_Suberrors
            }
            
            """User errors for AuthorId"""
            type UpdateBook_AuthorIdError {
              """An error code"""
              code: UpdateBook_AuthorIdErrorCode
            
              """A natural language description of the issue"""
              msg: String
            }
            
            """Error code"""
            enum UpdateBook_AuthorIdErrorCode {
              unknownAuthor
            }
            
            """User Error"""
            type UpdateBook_Suberrors {
              """Error for authorId"""
              authorId: UpdateBook_AuthorIdError
            }

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }
}
