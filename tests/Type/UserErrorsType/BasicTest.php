<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
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
            """User errors for UpsertSku"""
            type UpsertSkuError {
            
            }
		'), SchemaPrinter::printType($type));
    }

    public function testValidationOnSelf()
    {
        $type = new UserErrorsType([
            'errorCodes' => ['somethingWrong'],
            'type' => Type::id(),
        ], ['upsertSku']);

        self::assertEquals(Utils::nowdoc('
            """User errors for UpsertSku"""
            type UpsertSkuError {
              """An error code"""
              code: UpsertSkuErrorCode
            
              """A natural language description of the issue"""
              msg: String
            }
		'), SchemaPrinter::printType($type));
    }

    public function testValidationWithNoErrorCodes()
    {
        $type = UserErrorsType::create([
            'validate' => static function ($value) {
            },
            'type' => Type::id(),
        ], ['upsertSku']);

        self::assertEquals(Utils::nowdoc('
            """User errors for UpsertSku"""
            type UpsertSkuError {
              """A numeric error code. 0 on success, non-zero on failure."""
              code: Int
            
              """An error message."""
              msg: String
            }
		'), SchemaPrinter::printType($type));
    }
}
