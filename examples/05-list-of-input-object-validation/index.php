<?php declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use GraphQL\GraphQL;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidatedFieldDefinition;
use GraphQL\Type\Schema;

class AuthorType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'fields' => [
                'firstName' => [
                    'type' => Type::string(),
                    'resolve' => function ($author) {
                        return $author['firstName'];
                    },
                ],
                'lastName' => [
                    'type' => Type::string(),
                    'resolve' => function ($author) {
                        return $author['lastName'];
                    },
                ],
            ],
        ]);
    }
}

$authors = [
    1 => [
        'firstName' => 'John',
        'lastName' => 'Steinbeck',
    ],
    2 => [
        'firstName' => 'Jim',
        'lastName' => 'Thompson',
    ],
];

$authorType = new AuthorType();

try {
    $mutationType = new ObjectType([
        'name' => 'Mutation',
        'fields' => [
            'updateAuthors' => new ValidatedFieldDefinition([
                'name' => 'updateAuthors',
                'validate' => function (array $args) {
                    if (count($args['authors']) == 0) {
                        return [1, "You can't update more than 10 authors at one time"];
                    }

                    return 0;
                },
                'type' => Type::listOf($authorType),
                'args' => [
                    'authors' => [
                        'type' => Type::listOf(new InputObjectType([
                            'name' => 'AuthorInput',
                            'validate' => function ($args) {
                            },
                            'fields' => [
                                'id' => [
                                    'type' => Type::nonNull(Type::id()),
                                    'validate' => static function ($authorId) use ($authors) {
                                        if (empty($authors[$authorId])) {
                                            return [1, "We don't seem to have that author in our database"];
                                        }

                                        return 0;
                                    },
                                ],
                                'firstName' => [
                                    'type' => Type::nonNull(Type::string()),
                                    'validate' => static function (string $firstName) {
                                        if (strlen($firstName) <= 5) {
                                            return [1, 'First names must be longer than 5 characters'];
                                        }

                                        return 0;
                                    },
                                ],
                                'lastName' => [
                                    'type' => Type::nonNull(Type::string()),
                                    'validate' => static function (string $lastName) {
                                        if (strlen($lastName) > 6) {
                                            return [1, 'Last names must be under 6 characters long'];
                                        }

                                        return 0;
                                    },
                                ],
                            ],
                        ])),
                    ],
                ],
                'resolve' => function ($value, $args) use ($authors) {
                    // the 'type' of this big mess is a listOf Authors, and now that everything has passed validation
                    // we need to actually do the update, and then return the newly-updated list of authors.

                    foreach ($args['authors'] as $authorInput) {
                        $author = &$authors[$authorInput['id']];
                        $author['firstName'] = $authorInput['firstName'];
                        $author['lastName'] = $authorInput['lastName'];
                    }

                    return $authors;
                },
            ]),
        ],
    ]);

    $queryType = new ObjectType([
        'name' => 'Query',
        'fields' => [
            'author' => [
                'type' => Type::string(),
                'resolve' => function ($value, $args) {
                },
            ],
        ],
    ]);

    $schema = new Schema([
        'query' => $queryType,
        'mutation' => $mutationType,
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
