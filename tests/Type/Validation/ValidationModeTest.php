<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests\Type\Validation\ValidationModeTest;

use GraphQL\Tests\Type\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\PhpEnumType;
use GraphQL\Type\Definition\Type;
use GraphQlPhpValidationToolkit\Tests\Type\TestBase;
use GraphQlPhpValidationToolkit\Tests\Utils;
use GraphQlPhpValidationToolkit\Type\ErrorType\ValidatedFieldDefinition;

enum DingusError
{
    case dingusRequired;
}

enum Animal
{
    case mammal;
    case fish;
    case bird;
}

/**
 * Test the 'validationMode' config property
 */
final class ValidationModeTest extends TestBase
{
    /** @var mixed[] */
    protected $data = [
        'people' => [
            1 => ['firstName' => 'Wilson'],
            2 => ['firstName' => 'J.D.'],
            3 => ['firstName' => 'Diana'],
        ],
    ];

    /**
     * When 'validationMode' is set to 'partial', we only validate values that are passed in, so this should pass validation.
     */
    public function testValidationTypePartial(): void
    {
        $this->_checkValidation(
            new ValidatedFieldDefinition([
                'validationMode' => 'partial',
                'name' => 'updateBook',
                'type' => Type::boolean(),
                'args' => [
                    'bookAttributes' => [
                        'type' => function () { // lazy load
                            return new InputObjectType([
                                'name' => 'BookAttributes',
                                'fields' => [
                                    // basic required functionality
                                    'foo' => [
                                        'type' => Type::string(),
                                        'description' => 'Provide a foo',
                                        'required' => true,
                                    ],

                                    // custom required response (with [int, string])
                                    'bar' => [
                                        'type' => Type::string(),
                                        'description' => 'Provide a bar',
                                        'required' => [1, 'Oh, we absolutely must have a bar'],
                                    ],

                                    // required callback
                                    'naz' => [
                                        'type' => Type::string(),
                                        'description' => 'Provide a naz',
                                        'required' => static fn() => true,
                                    ],

                                    // custom required response (with [enum, string])
                                    'dingus' => [
                                        'type' => Type::string(),
                                        'errorCodes' => DingusError::class,
                                        'description' => 'Provide a bar',
                                        'required' => [DingusError::dingusRequired, 'Make with the dingus'],
                                    ],

                                    // list of scalar
                                    'gadgets' => [
                                        'type' => Type::listOf(Type::string()),
                                        'required' => true,
                                    ],

                                    // list of enum
                                    'animals' => [
                                        'type' => Type::listOf(new PhpEnumType(
                                            Animal::class,
                                            "Animal"
                                        )),
                                        'required' => true
                                    ],
                                ],
                            ]);
                        },
                    ],
                ],
                'resolve' => static function ($value): bool {
                    return !$value;
                },
            ]),
            Utils::nowdoc('
                mutation UpdateBook(
                        $bookAttributes: BookAttributes
                    ) {
                    updateBook (
                        bookAttributes: $bookAttributes
                    ) {
                        _valid
                        bookAttributes {
                            foo {
                                _code
                                _msg
                            }
                            bar {
                                _code
                                _msg
                            }
                            naz {
                                _code
                                _msg
                            }
                            dingus {
                                _code
                                _msg
                            }
                            gadgets {
                                _code
                                _msg
                            }
                        }
                        _result
                    }
                }
            '),
            [
                'bookAttributes' => [
                ],
            ],
            [
                '_valid' => true,
                'bookAttributes' => null,
                '_result' => true,
            ]
        );
    }
}
