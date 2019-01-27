<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;

final class ListOfTest extends TestCase
{
    public function testScalarTypeWithNoValidation()
    {
        $type = new UserErrorsType([
            'type' => Type::listOf(Type::id()),
        ], ['upsertSku']);

        self::assertEquals(Utils::nowdoc('
			"""User errors for UpsertSku"""
			type UpsertSkuError {
			
			}
		'), SchemaPrinter::printType($type));
    }

    public function testListOfScalarWithValidationOnSelf()
    {
        $type = new UserErrorsType([
            'type' => Type::listOf(Type::id()),
            'errorCodes'=> ['atLeastOneRequired'],
            'validation' => static function ($value) {
                return 0;
            },
        ], ['users']);

        self::assertEquals(Utils::nowdoc('
            """User errors for Users"""
            type UsersError {
              """An error code"""
              code: UsersErrorCode
            
              """A natural language description of the issue"""
              msg: String
            }
		'), SchemaPrinter::printType($type));
    }

    public function testListOfInputObjectWithValidationOnFields()
    {
        $types = [];
        $type  = new UserErrorsType([
            'type' => Type::listOf(new InputObjectType([
                'name'=> 'BookInput',
                'fields' => [
                    'authorId' => [
                        'type' => Type::id(),
                        'errorCodes' => ['unknownAuthor'],
                    ],
                ],
            ])),
            'typeSetter' => static function (Type $type) use (&$types) {
                $types[$type->name] = $type;
            },
        ], ['bookList']);

        self::assertEquals(Utils::nowdoc('
            """User errors for BookList"""
            type BookListError {
              """Suberrors for the list of BookInput items"""
              suberrors: [BookList_BookInputError]
            }
		'), SchemaPrinter::printType($type));

        self::assertTrue(isset($types['BookList_BookInputError']));
        self::assertTrue(isset($types['BookList_BookInput_Suberrors']));
        self::assertTrue(isset($types['BookList_BookInput_AuthorIdError']));
        self::assertTrue(isset($types['BookList_BookInput_AuthorIdErrorCode']));

        self::assertEquals(Utils::nowdoc('
            """User errors for BookInput"""
            type BookList_BookInputError {
              """Suberrors for BookInput"""
              suberrors: BookList_BookInput_Suberrors
            
              """The index of the array item this error is paired with"""
              index: Int
            }
        '), SchemaPrinter::printType($types['BookList_BookInputError']));

        self::assertEquals(Utils::nowdoc('
            """User Error"""
            type BookList_BookInput_Suberrors {
              """Error for authorId"""
              authorId: BookList_BookInput_AuthorIdError
            }
        '), SchemaPrinter::printType($types['BookList_BookInput_Suberrors']));

        self::assertEquals(Utils::nowdoc('
            """User errors for AuthorId"""
            type BookList_BookInput_AuthorIdError {
              """An error code"""
              code: BookList_BookInput_AuthorIdErrorCode
            
              """A natural language description of the issue"""
              msg: String
            }
        '), SchemaPrinter::printType($types['BookList_BookInput_AuthorIdError']));

        self::assertEquals(Utils::nowdoc('
            """Error code"""
            enum BookList_BookInput_AuthorIdErrorCode {
              unknownAuthor
            }
        '), SchemaPrinter::printType($types['BookList_BookInput_AuthorIdErrorCode']));
    }

    public function testListOfScalarWithValidationOnItemsAndOnSelf()
    {
        $types = [];
        $type  = new UserErrorsType([
            'type' => Type::listOf(Type::id()),
            'errorCodes' => ['atLeastOneRequired'],
            'suberrorCodes' => ['authorNotFound'],
            'validate' => static function ($items) {
            },
            'validateItem' => static function ($item) {
                return 0;
            },
            'typeSetter' => static function (Type $type) use (&$types) {
                $types[$type->name] = $type;
            },
        ], ['authorList']);

        self::assertEquals(Utils::nowdoc('
            """User errors for AuthorList"""
            type AuthorListError {
              """An error code"""
              code: AuthorListErrorCode
            
              """A natural language description of the issue"""
              msg: String
            
              """Suberrors for the list of ID items"""
              suberrors: [AuthorList_IDError]
            }
		'), SchemaPrinter::printType($type));

        self::assertTrue(isset($types['AuthorList_IDError']));
        self::assertTrue(isset($types['AuthorList_IDErrorCode']));
        self::assertTrue(isset($types['AuthorListErrorCode']));

        self::assertEquals(Utils::nowdoc('
            """User errors for ID"""
            type AuthorList_IDError {
              """An error code"""
              code: AuthorList_IDErrorCode
            
              """A natural language description of the issue"""
              msg: String
            
              """The index of the array item this error is paired with"""
              index: Int
            }
        '), SchemaPrinter::printType($types['AuthorList_IDError']));

        self::assertEquals(Utils::nowdoc('
            """Error code"""
            enum AuthorList_IDErrorCode {
              authorNotFound
            }
        '), SchemaPrinter::printType($types['AuthorList_IDErrorCode']));

        self::assertEquals(Utils::nowdoc('
            """Error code"""
            enum AuthorListErrorCode {
              atLeastOneRequired
            }
        '), SchemaPrinter::printType($types['AuthorListErrorCode']));
    }
}
