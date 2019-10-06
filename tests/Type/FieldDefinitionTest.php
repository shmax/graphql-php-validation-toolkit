<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type;

use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;
use function count;

abstract class FieldDefinitionTest extends TestCase
{
    protected function _checkSchema(ValidatedFieldDefinition $field, $expected): void {
        $mutation = new ObjectType([
            'name' => 'Mutation',
            'fields' => static function () use($field) {
                return [
                    $field->name => $field,
                ];
            },
        ]);

        self::assertEquals(Utils::nowdoc($expected), SchemaPrinter::doPrint(new Schema(['mutation' => $mutation])));
    }
}
