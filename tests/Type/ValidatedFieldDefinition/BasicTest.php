<?php

declare(strict_types=1);

namespace GraphQL\Tests\Type\ValidatedFieldDefinition;

use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\TestCase;

final class BasicTest extends TestCase
{
	public function testInferName()
	{
		$def = new NamelessDef([
			'type' => Type::boolean(),
			'args' => [
				'bookId' => [
					'type' => Type::id(),
					'errorCodes' => ['bookNotFound'],
					'validate' => function ($bookId) {
						return 0;
					},
				],
			],
			'resolve' => static function ($value, $args) : bool {
				return true;
			},
		]);

		static::assertEquals("namelessDef", $def->name);
	}

	public function testInferNameNameAlreadyExists()
	{
		$def = new NamelessDef([
			'name' => "FooDef",
			'type' => Type::boolean(),
			'args' => [
				'bookId' => [
					'type' => Type::id(),
					'errorCodes' => ['bookNotFound'],
					'validate' => function ($bookId) {
						return 0;
					},
				],
			],
			'resolve' => static function ($value, $args) : bool {
				return true;
			},
		]);

		static::assertEquals("FooDef", $def->name);
	}
}
