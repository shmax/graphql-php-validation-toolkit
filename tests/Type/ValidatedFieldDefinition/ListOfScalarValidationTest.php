<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\ValidatedFieldDefinition;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\UserErrorType\ValidatedFieldDefinition;
use GraphQlPhpValidationToolkit\Type\ValidatedStringType;
use PHPUnit\Framework\TestCase;

final class ListOfScalarValidationTest extends TestBase
{
    public function testListOfStringType(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
                'name' => 'savePhoneNumbers',
                'type' => Type::boolean(),
                'args' => [
                    'phoneNumbers' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Enter a list of names. We'll validate each one",
                        'item' => ['validate' => static function ($value) {
                            return strlen($value) <= 7 ? 0 : 1;
                        }]
                    ],
                ],
                'resolve' => static function ($value): bool {
                    return true;
                },
            ]),
            Utils::nowdoc('
                mutation SavePhoneNumbers($phoneNumbers: [String]) {
                    savePhoneNumbers (
                        phoneNumbers: $phoneNumbers
                    ) {
                        valid
                        fieldErrors {
                            phoneNumbers {
                                items {
                                    code
                                    msg
                                    path
                                }
                            }
                        }
                        result
                    }
                }
            '),
            [
                'phoneNumbers' => [
                    '1',
                    '2',
                    '3'
                ],
            ],
            [
                'valid' => false,
                'code' => 1,
                'msg' => "If title is set, then author is required",
                'items' => [
                    'phoneNumbers' => [
                        'code' => 1,
                        'msg' => 'book title must be less than 10 characters',
                    ],
                ],
                'result' => null,
            ]
        );
    }

}
