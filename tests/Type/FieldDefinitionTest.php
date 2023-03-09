<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type;

use GraphQL\GraphQL;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NamedType;
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
//    protected $outputPath = 'tmp/';
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

    protected function _checkValidation(ValidatedFieldDefinition $field, string $qry, array $args, array $expected ) {
        $schema = new Schema([
            'query' => new ObjectType(['name' => 'Query', 'fields' => []]),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => static function () use($field) {
                    return [
                        $field->name => $field,
                    ];
                },
            ])
        ]);

        $res = GraphQL::executeQuery(
            $schema,
            $qry,
            [],
            null,
            $args
        );

        static::assertEquals([], $res->errors, "There should be no errors in your query");
        static::assertEquals($expected, $res->data[$field->name]);

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
            return ! $type->isBuiltInType();
        });


        $typeMap = array_map(function($type) {
            $type->description = null;
            return Utils::toNowDoc(SchemaPrinter::printType($type), 8);
        }, $types);

        if(!empty($this->outputPath)) {
            $lines = preg_split('/\\n/', Utils::varExport($typeMap, true));
            $numLines = count($lines);
            for ($i = 0; $i < $numLines; $i++) {
                $lines[$i] = str_repeat(" ", 12) . $lines[$i];
            }

            file_put_contents($this->outputPath . 'schema.php', implode("\n", $lines));
        }

        foreach($expectedMap as $typeName => $expected) {
            self::assertEquals(Utils::nowdoc($expected), SchemaPrinter::printType($types[$typeName]));
        }
    }
}
