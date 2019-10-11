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

    protected function _checkTypes($field, array $expectedMap): void {
        $mutation = new ObjectType([
            'name' => 'Mutation',
            'fields' => static function () use($field) {
                return [
                    $field->name => $field,
                ];
            },
        ]);

        $schema = new Schema(['mutation' => $mutation]);

        $types = $schema->getTypeMap();

        $types = array_filter($types, function($type) {
            return ! Type::isBuiltInType($type);
        });


        $t = array_map(function($type) {
            $type->description = null;
            return Utils::toNowDoc(SchemaPrinter::printType($type), 8);
        }, $types);

        $lines = preg_split('/\\n/', Utils::varExport($t, true));

        $numLines = count($lines);
        for ($i = 0; $i < $numLines; $i++) {
            $lines[$i] = str_repeat(" ", 12) .$lines[$i];
        }

        file_put_contents("D:/Users/Shmax/schema.php", implode("\n", $lines));

        foreach($expectedMap as $typeName => $expected) {
            self::assertEquals(Utils::nowdoc($expected), SchemaPrinter::printType($types[$typeName]));
        }
    }
}
