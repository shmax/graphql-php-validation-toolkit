<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ErrorType;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidatedFieldDefinition;
use PHPUnit\Framework\TestCase;

abstract class TestBase extends TestCase
{
//    protected $outputPath = 'tmp/';
    protected function _checkSchema(Type $field, string $expected): void
    {
        $actual = SchemaPrinter::doPrint(new Schema(['mutation' => $field]));
        self::assertEquals(Utils::nowdoc($expected), $actual);
    }

    protected function _checkType(Type $type, string $expected): void {
        $actual = SchemaPrinter::printType($type);
        self::assertEquals(Utils::nowdoc($expected), $actual);
    }


    /**
     * @param array<string, mixed>|null $args
     * @param array<mixed> $expected
     * @throws \Exception
     */
    protected function _checkValidation(ValidatedFieldDefinition $field, string $qry, $args, array $expected): void
    {
        $schema = new Schema([
            'query' => new ObjectType(['name' => 'Query', 'fields' => []]),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => static function () use ($field) {
                    return [
                        $field->name => $field,
                    ];
                },
            ]),
        ]);

        $res = GraphQL::executeQuery(
            $schema,
            $qry,
            [],
            null,
            $args
        );

        static::assertEquals([], $res->errors, 'There should be no errors in your query');
        static::assertEquals($expected, $res->data[$field->name]);
    }

    /**
     * @param array<string, string> $expectedMap
     */
    protected function _checkTypes(ErrorType $field, array $expectedMap): void
    {
        $mutation = new ObjectType([
            'name' => 'Mutation',
            'fields' => static function () use ($field) {
                return [
                    $field->name => $field,
                ];
            },
        ]);

        $schema = new Schema(['mutation' => $mutation]);

        $types = $schema->getTypeMap();

        $types = array_filter($types, function ($type) {
            return ! $type->isBuiltInType();
        });

        $typeMap = array_map(function ($type) {
            $type->description = null;

            return Utils::toNowDoc(SchemaPrinter::printType($type), 8);
        }, $types);

        if (! empty($this->outputPath)) {
            $lines = preg_split('/\\n/', Utils::varExport($typeMap, true));
            assert($lines !== false);
            $numLines = \count($lines);
            for ($i = 0; $i < $numLines; ++$i) {

                $lines[$i] = str_repeat(' ', 12) . $lines[$i];
            }

            file_put_contents($this->outputPath . 'schema.php', implode("\n", $lines));
        }

        foreach ($expectedMap as $typeName => $expected) {
            $actual = SchemaPrinter::printType($types[$typeName]);
            self::assertEquals(Utils::nowdoc($expected), $actual);
        }
    }
}
