<?php declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use GraphQL\GraphQL;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;

enum NameErrors {
    case NameTooLong;
    case NameNotUnique;
}

enum AgeErrors {
    case Negative;
}

enum AuthorErrors {
    case UnknownAuthor;
    case DeceasedAuthor;
}

$authors = [
    1 => [
        'id' => 1,
        'name' => 'Cormac McCarthy',
        'deceased' => false,
    ],
    2 => [
        'id' => 2,
        'name' => 'J.D.Salinger',
        'deceased' => true,
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

class AuthorAttributes extends InputObjectType
{
    public function __construct()
    {
        parent::__construct([
            'fields' => [
                'name' => [
                    'type' => Type::string(),
                    'errorCodes' => NameErrors::class,
                    'validate' => function (string $name) {
                        global $authors;

                        if (strlen($name) > 15) {
                            return [NameErrors::NameTooLong, 'Name is too long; please keep it under 15 characters.'];
                        }

                        if (array_search($name, array_column($authors, 'name')) !== false) {
                            return [NameErrors::NameNotUnique, 'That name is already in use'];
                        }

                        return 0;
                    },
                ],
                'age' => [
                    'type' => Type::int(),
                    'errorCodes' => AgeErrors::class,
                    'validate' => function (int $age) {
                        if ($age <= 0) {
                            return [
                                AgeErrors::Negative,
                                'Invalid Age; must be positive'
                            ];
                        }

                        return 0;
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
            'updateAuthor' => new ValidatedFieldDefinition([
                'name' => 'updateAuthor',
                'type' => $authorType,
                'args' => [
                    'authorId' => [
                        'type' => Type::id(),
                        'errorCodes' => AuthorErrors::class,
                        'validate' => function (string $authorId) use ($authors) {
                            if (! isset($authors[$authorId])) {
                                return [AuthorError::UnknownAuthor, 'We have no record of that author'];
                            }

                            return 0;
                        },
                    ],
                    'attributes' => [
                        'type' => new AuthorAttributes(),
                        'validate' => static function ($attributes) {
                            // you can do "meta-validation" here, eg check to make sure that certain
                            // combinations of fields are present, or that one field is not conflicting
                            // with another, etc
                            return 0;
                        },
                    ],
                ],
                'resolve' => function ($value, $args) use ($authors) {
                    $authorId = $args['authorId'];

                    // AuthorProvider::update($authorId, $args['attributes']);
                    return $authors[$authorId];
                },
            ]),
        ],
    ]);

    $queryType = new ObjectType([
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
    ]);

    $schema = new Schema([
        'mutation' => $mutationType,
        'query' => $queryType,
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
