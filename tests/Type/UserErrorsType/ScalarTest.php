<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\UserErrorsType;

use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\UserErrorType\UserErrorsType;
use PHPUnit\Framework\TestCase;

enum ColorErrors {
    case invalidColor;
}

final class ScalarTest extends TestCase
{
    public function testWithValidation(): void
    {
        $type = UserErrorsType::create([
            'errorCodes' => ColorErrors::class,
            'validate' => static function ($value) {
                return $value ? 0 : ColorErrors::invalidColor;
            },
            'type' => new IDType(['name' => 'Color']),
        ], ['palette']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: PaletteError
            }
            
            "User errors for Palette"
            type PaletteError {
              "An enumerated error code."
              code: PaletteErrorCode
            
              "An error message."
              msg: String
            }
            
            enum PaletteErrorCode {
              invalidColor
            }

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }
}
