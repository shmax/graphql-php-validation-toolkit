<?php declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;

try {
    $mutationType = new ObjectType([
        'name' => 'Mutation',
        'fields' => [
            'savePhoneNumbers' => new ValidatedFieldDefinition([
                'name' => 'savePhoneNumbers',
                'type' => Type::boolean(),
                'validate' => function (array $args) {
                    if (count($args['phoneNumbers']) == 0) {
                        return [1, 'You must enter at least one list of phone number'];
                    }

                    return 0;
                },
                'args' => [
                    'phoneNumbers' => [
                        'validate' => function ($phoneNumber) {
                            $res = preg_match('/^[0-9\-]+$/', $phoneNumber) === 1;

                            return ! $res ? [1, 'That does not seem to be a valid phone number'] : 0;
                        },
                        'type' => Type::listOf(Type::string()),
                    ],
                ],
                'resolve' => function ($value, $args) {
                    // PhoneNumberProvider::setPhoneNumbers($args['phoneNumbers']);
                    return true;
                },
            ]),
        ],
    ]);

    $queryType = new ObjectType([
        'name' => 'Query',
        'fields' => [
            'foo' => [
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
