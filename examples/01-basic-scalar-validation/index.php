<?php declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;

$authors = [
    1 => [
        'id' => 1,
        'name' => 'Cormac McCarthy',
    ],
    2 => [
        'id' => 2,
        'name' => 'J.D.Salinger',
    ],
];

class AuthorType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'fields' => [
                'id' => [
                    'type' => Type::id(),
                    'resolve' => function ($author) {
                        return $author['id'];
                    },
                ],
                'name' => [
                    'type' => Type::string(),
                    'resolve' => function ($author) {
                        return $author['name'];
                    },
                ],
            ],
        ]);
    }
}

$authorType = new AuthorType();

try {
    $mutationType = new ObjectType([
        'name' => 'Mutation',
        'fields' => [
            'deleteAuthor' => new ValidatedFieldDefinition([
                'name' => 'deleteAuthor',
                'type' => Type::boolean(),
                'args' => [
                    'id' => [
                        'type' => Type::id(),
                        'validate' => function (string $authorId) use ($authors) {
                            if (isset($authors[$authorId])) {
                                return 0;
                            }

                            return [1, 'Unknown author'];
                        },
                    ],
                ],
                'resolve' => function ($value, $args) use ($authors) {
                    // do your operation on the author
                    // AuthorProvider::update($authorId);
                    unset($authors[$args['id']]);

                    return true;
                },
            ]),
        ],
    ]);

    $schema = new Schema([
        'mutation' => $mutationType,
        'query' => new ObjectType([
            'name' => 'Query',
            'fields' => [
                'author' => [
                    'type' => $authorType,
                    'args' => [
                        'authorId' => [
                            'type' => Type::id(),
                        ],
                    ],
                    'resolve' => function ($value, $args) use ($authors) {
                        return $authors[$args['authorId']];
                    },
                ],
            ],
        ]),
    ]);

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    $query = $input['query'];
    $variableValues = isset($input['variables']) ? $input['variables'] : null;
    $result = GraphQL::executeQuery($schema, $query, null, null, $variableValues);
    $output = $result->toArray();
} catch (\Exception $e) {
    $output = [
        'error' => [
            'message' => $e->getMessage(),
        ],
    ];
}
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($output);
