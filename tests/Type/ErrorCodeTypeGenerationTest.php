<?php declare(strict_types=1);

namespace GraphQL\Tests\Type;

use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\SchemaPrinter;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\Definition\UserErrorsType;
use PHPUnit\Framework\TestCase;

enum UserValidation {
    case UnknownUser;
    case UserIsMinor;
}

enum AuthorValidation {
    case UnknownAuthor;
}

final class ErrorCodeTypeGenerationTest extends TestCase
{
    public function testUniqueNameWhenNoTypeSetter(): void
    {
        $type = UserErrorsType::create([
            'validate' => static function ($val) {
                return $val ? 0 : 1;
            },
            'errorCodes' => UserValidation::class,
            'type' => new IDType(['name' => 'User']),
        ], ['updateUser']);

        $generatedErrorType = $type->config['fields']['code']['type'];

        static::assertTrue( $generatedErrorType->name == 'UpdateUserErrorCode');

        self::assertEquals(
            SchemaPrinter::printType($generatedErrorType),
            Utils::nowdoc('
                "Error code"
                enum UpdateUserErrorCode {
                  UnknownUser
                  UserIsMinor
                }
        ')
        );
    }

    public function testShortNameWhenTypeSetter(): void
    {
        $types = [];
        new UserErrorsType([
            'validate' => static function ($val) {
                return $val ? 0 : 1;
            },
            'errorCodes' => UserValidation::class,
            'typeSetter' => static function ($type) use (&$types): Type {
                if(!isset($types[$type->name])) {
                    $types[$type->name] = $type;
                }
                return $types[$type->name];
            },
            'type' => new IDType(['name' => 'User']),
        ], ['updateUser']);

        static::assertTrue(isset($types['UserValidationErrorCode']));

        self::assertEquals(
            SchemaPrinter::printType($types['UserValidationErrorCode']),
            Utils::nowdoc('
                "Error code"
                enum UserValidationErrorCode {
                  UnknownUser
                  UserIsMinor
                }
        ')
        );
    }

    public function testFieldsWithNoErrorCodes(): void
    {
        $types = [];
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

        self::assertEquals($types, []);
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
                        'errorCodes' => AuthorValidation::class,
                        'type' => Type::id(),
                        'description' => 'An author Id',
                    ],
                ],
            ]),
            'typeSetter' => static function ($type) use (&$types): Type {
                $types[$type->name] = $type;
                return $types[$type->name];
            },
        ], ['updateBook']);

        self::assertCount(2, \array_keys($types));
        self::assertTrue(isset($types['AuthorValidationErrorCode']));
        self::assertEquals(
            Utils::nowdoc('
                "Error code"
                enum AuthorValidationErrorCode {
                  UnknownAuthor
                }
            '),
            SchemaPrinter::printType($types['AuthorValidationErrorCode']),
        );
    }
}
