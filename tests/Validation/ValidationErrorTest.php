<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Validation;

use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\TestBase;
use GraphQlPhpValidationToolkit\TypeRegistry;
use PHPUnit\Framework\Attributes\TestDox;

final class ValidationErrorTest extends TestBase
{
    #[TestDox("A plain ValidationErrorType validates")]
    public function testNullableScalarValidationOnNullValueSuccess(): void
    {
        $validationError = TypeRegistry::validationError();
        $res = $validationError->validate(['type' => Type::id()], null, ['type' => Type::id(), 'args' => []]);

        static::assertEquals($res, []);

    }
}
