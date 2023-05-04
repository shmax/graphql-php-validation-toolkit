<?php declare(strict_types=1);

namespace GraphQL\Tests\Type\ValidatedFieldDefinition;

use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\TestCase;

final class BasicTest extends TestCase
{
    public function testInferName(): void
    {
        $def = new NamelessDef([
            'type' => Type::boolean(),
            'args' => [
                'bookId' => [
                    'type' => Type::id(),
                    'validate' => static function ($bookId) {
                        return $bookId ? 0 : 1;
                    },
                ],
            ],
            'resolve' => static function ($value): bool {
                return (bool) $value;
            },
        ]);

        static::assertEquals('namelessDef', $def->name);
    }

    public function testInferNameNameAlreadyExists(): void
    {
        $def = new NamelessDef([
            'name' => 'FooDef',
            'type' => Type::boolean(),
            'args' => [
                'bookId' => [
                    'type' => Type::id(),
                    'validate' => static function ($bookId) {
                        return $bookId ? 0 : 1;
                    },
                ],
            ],
            'resolve' => static function ($value): bool {
                return (bool) $value;
            },
        ]);

        static::assertEquals('FooDef', $def->name);
    }
}
