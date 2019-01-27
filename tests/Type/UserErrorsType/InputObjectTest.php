<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;
use function array_keys;

final class InputObjectTest extends TestCase
{
    public function testFieldsWithNoErrorCodes()
    {
        $types = [];
        $type  = new UserErrorsType([
            'type' => new InputObjectType([
                'name' => 'bookInput',
                'fields' => [
                    'authorId' => [
                        'type' => Type::id(),
                        'description' => 'An author Id',
                    ],
                ],
            ]),
            'typeSetter' => static function ($type) use (&$types) {
                $types[$type->name] = $type;
            },
        ], ['updateBook']);

        self::assertCount(0, array_keys($types));
        self::assertEquals(
            SchemaPrinter::printType($type),
            Utils::nowdoc('
                """User errors for UpdateBook"""
                type UpdateBookError {
                
                }
        ')
        );
    }

    public function testFieldsWithErrorCodes()
    {
        $types = [];
        $type  = new UserErrorsType([
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
            'typeSetter' => static function ($type) use (&$types) {
                $types[$type->name] = $type;
            },
        ], ['updateBook']);

        self::assertCount(3, array_keys($types));
        self::assertTrue(isset($types['UpdateBook_AuthorIdError']));
        self::assertTrue(isset($types['UpdateBook_Suberrors']));

        self::assertEquals(
            SchemaPrinter::printType($type),
            Utils::nowdoc('
                """User errors for UpdateBook"""
                type UpdateBookError {
                  """Suberrors for UpdateBook"""
                  suberrors: UpdateBook_Suberrors
                }
            ')
        );

        self::assertEquals(
            Utils::nowdoc('
                """User Error"""
                type UpdateBook_Suberrors {
                  """Error for authorId"""
                  authorId: UpdateBook_AuthorIdError
                }
            '),
            SchemaPrinter::printType($types['UpdateBook_Suberrors'])
        );

        self::assertEquals(
            Utils::nowdoc('
                """User errors for AuthorId"""
                type UpdateBook_AuthorIdError {
                  """An error code"""
                  code: UpdateBook_AuthorIdErrorCode
                
                  """A natural language description of the issue"""
                  msg: String
                }
            '),
            SchemaPrinter::printType($types['UpdateBook_AuthorIdError'])
        );
    }
}
