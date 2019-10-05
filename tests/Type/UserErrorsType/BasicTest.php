<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;

final class BasicTest extends TestCase
{
    public function testNoValidationOnSelf()
    {
        $type = new UserErrorsType([
            'type' => Type::id(),
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

    public function testNoType()
    {
        $this->expectExceptionMessage('You must specify a type for your field');
        UserErrorsType::create([
            'validate' => static function ($value) {
                return $value ? 0 : 1;
            },
        ], ['upsertSku']);
    }

    public function testValidationWithNoErrorCodes()
    {
        $type = UserErrorsType::create([
            'validate' => static function ($value) {
            },
            'type' => Type::id(),
        ], ['upsertSku']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: UpsertSkuError
            }
            
            """User errors for UpsertSku"""
            type UpsertSkuError {
              """A numeric error code. 0 on success, non-zero on failure."""
              code: Int
            
              """An error message."""
              msg: String
            }

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }
}
