<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;

final class NonNullTest extends TestCase
{
    public function testStringWrappedType(): void
    {
        $type = new UserErrorsType([
            'errorCodes' => ['invalidColor'],
            'validate' => static function ($value) {
                return $value ? 0 : 1;
            },
            'type' => Type::nonNull(Type::string()),
        ], ['authorId']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: AuthorIdError
            }
            
            """User errors for AuthorId"""
            type AuthorIdError {
              """An error code"""
              code: AuthorIdErrorCode
            
              """A natural language description of the issue"""
              msg: String
            }
            
            """Error code"""
            enum AuthorIdErrorCode {
              invalidColor
            }

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }

    public function testInputObjectWrappedType(): void
    {
        $type = new UserErrorsType([
            'errorCodes' => ['invalidColor'],
            'validate' => static function ($value) {
                return $value ? 0 : 1;
            },
            'type' => Type::nonNull(new InputObjectType([
                'name' => 'bookInput',
                'fields' => [
                    'authorId' => [
                        'type' => Type::id(),
                        'description' => 'An author Id',
                        'validate' => static function($authorId) {
                            return !empty($authorId);
                        }
                    ],
                ],
            ])),
        ], ['author']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: AuthorError
            }
            
            """User errors for Author"""
            type AuthorError {
              """An error code"""
              code: AuthorErrorCode
            
              """A natural language description of the issue"""
              msg: String
            
              """Suberrors for Author"""
              suberrors: Author_Suberrors
            }
            
            """Error code"""
            enum AuthorErrorCode {
              invalidColor
            }
            
            """User errors for AuthorId"""
            type Author_AuthorIdError {
              """A numeric error code. 0 on success, non-zero on failure."""
              code: Int
            
              """An error message."""
              msg: String
            }
            
            """User Error"""
            type Author_Suberrors {
              """Error for authorId"""
              authorId: Author_AuthorIdError
            }

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }
}
