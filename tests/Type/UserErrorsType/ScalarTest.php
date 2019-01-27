<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;

final class ScalarTest extends TestCase
{
    public function testNoValidation()
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

    public function testWithValidation()
    {
        $type = new UserErrorsType([
            'errorCodes' => ['invalidColor'],
            'validation' => static function ($value) {
                return 0;
            },
            'typeSetter' => static function ($type) use (&$types) {
                $types[$type->name] = $type;
            },
            'type' => new IDType(['name' => 'Color']),
        ], ['palette']);

        self::assertEquals(Utils::nowdoc('
            """User errors for Palette"""
            type PaletteError {
              """An error code"""
              code: PaletteErrorCode
            
              """A natural language description of the issue"""
              msg: String
            }
		'), SchemaPrinter::printType($type));
    }
}
