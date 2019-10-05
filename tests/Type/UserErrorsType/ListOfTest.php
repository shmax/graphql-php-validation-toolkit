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
use function count;

final class ListOfTest extends TestCase
{
    public function testScalarTypeWithNoValidation()
    {
        $type = new UserErrorsType([
            'type' => Type::listOf(Type::id()),
        ], ['upsertSku']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: UpsertSkuError
            }
            
            """User errors for UpsertSku"""
            type UpsertSkuError {
            
            }

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }

    public function testListOfScalarWithValidationOnSelf()
    {
        $type = new UserErrorsType([
            'type' => Type::listOf(Type::id()),
            'errorCodes'=> ['atLeastOneRequired'],
            'validate' => static function ($value) {
                return 0;
            },
        ], ['users']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: UsersError
            }
            
            """User errors for Users"""
            type UsersError {
              """An error code"""
              code: UsersErrorCode
            
              """A natural language description of the issue"""
              msg: String
            }
            
            """Error code"""
            enum UsersErrorCode {
              atLeastOneRequired
            }

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }

    public function testListOfListOfListOfScalarWithValidationOnSelfAndWrappedType()
    {
        $type = new UserErrorsType([
            'type' => Type::listOf(Type::listOf(Type::listOf(Type::id()))),
            'errorCodes'=> ['atLeastOneRequired'],
            'validate' => static function ($value) {
                return 0;
            },
            'validateItem' => static function ($value) {
                return 0;
            },
        ], ['users']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: UsersError
            }
            
            """User errors for Users"""
            type UsersError {
              """An error code"""
              code: UsersErrorCode
            
              """A natural language description of the issue"""
              msg: String
            
              """Suberrors for the list of [[ID]] items"""
              suberrors: Users_IDError
            }
            
            """Error code"""
            enum UsersErrorCode {
              atLeastOneRequired
            }
            
            """User errors for ID"""
            type Users_IDError {
              """A numeric error code. 0 on success, non-zero on failure."""
              code: Int
            
              """An error message."""
              msg: String
            
              """A path describing this items\'s location in the nested array"""
              path: [Int]
            }
            
        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }

    public function testListOfInputObjectWithValidationOnSelf()
    {
        $type = new UserErrorsType([
            'validate' => static function (array $authors) {
                if (count($authors) < 1) {
                    return ['atLeastOneAuthorRequired', 'You must submit at least one author'];
                }
            },
            'errorCodes' => ['atLeastOneBookRequired'],
            'type' => Type::listOf(new InputObjectType([
                'name'=> 'Author',
                'fields' => [
                    'firstName' => [
                        'type' => Type::string(),
                    ],
                    'lastName' => [
                        'type' => Type::string(),
                    ],
                ],
            ])),
        ], ['authorList']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: AuthorListError
            }
            
            """User errors for AuthorList"""
            type AuthorListError {
              """An error code"""
              code: AuthorListErrorCode
            
              """A natural language description of the issue"""
              msg: String
            }
            
            """Error code"""
            enum AuthorListErrorCode {
              atLeastOneBookRequired
            }

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }

    public function testListOfInputObjectWithValidationOnWrappedSelf()
    {
        $type = new UserErrorsType([
            'suberrorCodes' => ['firstNameOrLastNameRequired'],
            'validateItem' => static function (array $author) {
                if (! isset($author['firstName']) && ! isset($author['lastName'])) {
                    return ['atLeastOneAuthorRequired', 'You must submit a first name or a last name'];
                }
            },
            'type' => Type::listOf(new InputObjectType([
                'name'=> 'Author',
                'fields' => [
                    'firstName' => [
                        'type' => Type::string(),
                        'validate' => static function (string $name) {
                            return $name ? 0 : 1;
                        },
                    ],
                    'lastName' => [
                        'type' => Type::string(),
                    ],
                ],
            ])),
        ], ['authorList']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: AuthorListError
            }
            
            """User errors for AuthorList"""
            type AuthorListError {
              """Suberrors for the list of Author items"""
              suberrors: AuthorList_AuthorError
            }
            
            """User errors for Author"""
            type AuthorList_AuthorError {
              """An error code"""
              code: AuthorList_AuthorErrorCode
            
              """A natural language description of the issue"""
              msg: String
            
              """Suberrors for Author"""
              suberrors: AuthorList_Author_Suberrors
            
              """A path describing this items\'s location in the nested array"""
              path: [Int]
            }
            
            """Error code"""
            enum AuthorList_AuthorErrorCode {
              firstNameOrLastNameRequired
            }
            
            """User errors for FirstName"""
            type AuthorList_Author_FirstNameError {
              """A numeric error code. 0 on success, non-zero on failure."""
              code: Int
            
              """An error message."""
              msg: String
            }
            
            """User Error"""
            type AuthorList_Author_Suberrors {
              """Error for firstName"""
              firstName: AuthorList_Author_FirstNameError
            }

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }
}
