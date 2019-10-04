<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Schema;
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
			schema {
			  query: UpsertSkuError
			}
			
			"""User errors for UpsertSku"""
			type UpsertSkuError {
			
			}

		'), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }

    public function testWithValidation()
    {
        $type = new UserErrorsType([
            'errorCodes' => ['invalidColor'],
            'validate' => static function ($value) {
                return 0;
            },
            'type' => new IDType(['name' => 'Color']),
        ], ['palette']);

        self::assertEquals(Utils::nowdoc('
			schema {
			  query: PaletteError
			}
			
			"""User errors for Palette"""
			type PaletteError {
			  """An error code"""
			  code: PaletteErrorCode
			
			  """A natural language description of the issue"""
			  msg: String
			}
			
			"""Error code"""
			enum PaletteErrorCode {
			  invalidColor
			}

		'), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }
}
