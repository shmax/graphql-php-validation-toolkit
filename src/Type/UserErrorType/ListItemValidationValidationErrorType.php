<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\PhpEnumType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;

class ListItemValidationValidationErrorType extends ValidationErrorType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'ListItemValidationError',
            'validate' => static fn() => null,
            'fields' => [
                ListOfValidationErrorType::PATH_NAME => [
                    'type' => Type::listOf(Type::int()),
                    'description' => 'A path describing this item\'s location in the nested array',
                    'resolve' => static function ($value) {
                        return $value[ListOfValidationErrorType::PATH_NAME];
                    },
                ]
            ],
        ], []);
    }
}
