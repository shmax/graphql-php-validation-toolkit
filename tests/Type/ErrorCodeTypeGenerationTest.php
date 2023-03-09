<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;
use function array_keys;

final class ErrorCodeTypeGenerationTest extends TestCase
{
    public function testMultipleErrorCodesOnSelf(): void
    {
        $types = [];
        new UserErrorsType([
            'validate' => static function ($val) {
                return $val ? 0 : 1;
            },
            'errorCodes' => [
                'unknownUser',
                'userIsMinor',
            ],
            'typeSetter' => static function ($type) use (&$types): void {
                $types[$type->name] = $type;
            },
            'type' => new IDType(['name' => 'User']),
        ], ['updateUser']);

        static::assertTrue(isset($types['UpdateUserErrorCode']));

        self::assertEquals(
            SchemaPrinter::printType($types['UpdateUserErrorCode']),
            Utils::nowdoc('
                "Error code"
                enum UpdateUserErrorCode {
                  unknownUser
                  userIsMinor
                }
        ')
        );
    }

    public function testFieldsWithNoErrorCodes(): void
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
            'typeSetter' => static function ($type) use (&$types): void {
                $types[$type->name] = $type;
            },
        ], ['updateBook']);

        self::assertEmpty($types);
        self::assertEquals(SchemaPrinter::printType($type), Utils::nowdoc('
            "User errors for UpdateBook"
            type UpdateBookError
        '));
    }

    public function testFieldsWithErrorCodes(): void
    {
        $types = [];
        new UserErrorsType([
            'type' => new InputObjectType([
                'name' => 'bookInput',
                'fields' => [
                    'authorId' => [
                        'validate' => static function ($authorId) {
                            return $authorId ? 0 : 1;
                        },
                        'errorCodes' => ['unknownAuthor'],
                        'type' => Type::id(),
                        'description' => 'An author Id',
                    ],
                ],
            ]),
            'typeSetter' => static function ($type) use (&$types): void {
                $types[$type->name] = $type;
            },
        ], ['updateBook']);

        self::assertCount(2, array_keys($types));
        self::assertTrue(isset($types['UpdateBook_AuthorIdErrorCode']));
        self::assertEquals(
            Utils::nowdoc('
                "Error code"
                enum UpdateBook_AuthorIdErrorCode {
                  unknownAuthor
                }
            '),
            SchemaPrinter::printType($types['UpdateBook_AuthorIdErrorCode']),
        );
    }
}
