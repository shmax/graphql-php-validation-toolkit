<?php declare(strict_types=1);

namespace GraphQL\Tests\Type\UserErrorsType;

use GraphQL\Tests\Type\FieldDefinition;
use GraphQL\Tests\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UserErrorsType;
use GraphQL\Type\Definition\UserErrorsType_bak;
use GraphQL\Type\Definition\ValidatedFieldDefinition;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;

final class Basic extends FieldDefinition
{
    public function testNoValidationOnSelf(): void
    {
        $type = UserErrorsType::create([
            'type' => Type::id(),
        ], ['upsertSku']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: UpsertSkuError
            }

            "User errors for UpsertSku"
            type UpsertSkuError

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }

    public function testRenameResultsField(): void
    {
        $this->_checkSchema(
            new ValidatedFieldDefinition([
                'type' => Type::boolean(),
                'name' => 'updateBook',
                'resultName' => '_result',
                'validate' => static function () {},
                'args' => [],
                'resolve' => static function (array $data): bool {
                    return ! empty($data);
                },
            ]),
            '
            type Mutation {
              updateBook: UpdateBookResult
            }
            
            "User errors for UpdateBook"
            type UpdateBookResult {
              "The payload, if any"
              _result: Boolean
            
              "Whether all validation passed. True for yes, false for no."
              valid: Boolean!
            
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            }

        '
        );
    }

    public function testRenameValidField(): void
    {
        $this->_checkSchema(
            new ValidatedFieldDefinition([
                'type' => Type::boolean(),
                'name' => 'updateBook',
                'validName' => '_valid',
                'validate' => static function () {},
                'args' => [],
                'resolve' => static function (array $data): bool {
                    return ! empty($data);
                },
            ]),
            '
            type Mutation {
              updateBook: UpdateBookResult
            }
            
            "User errors for UpdateBook"
            type UpdateBookResult {
              "The payload, if any"
              result: Boolean
            
              "Whether all validation passed. True for yes, false for no."
              _valid: Boolean!
            
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            }

        '
        );
    }

    public function testNoValidateCallbacks(): void
    {
        $this->expectExceptionMessage("You must specify at least one 'validate' callback somewhere");
        UserErrorsType::create([
            'type' => new InputObjectType([
                'name' => 'book',
                'fields' => [
                    'author' => [
                        'type' => new InputObjectType([
                            'name' => 'address',
                            'fields' => [
                                'zip' => [
                                    'type' => Type::string(),
                                ],
                            ],
                        ]),
                    ],
                ],
            ]),
        ], ['updateBook']);
    }

    public function testValidationWithNoErrorCodes(): void
    {
        $type = UserErrorsType::create([
            'validate' => static function ($value) {
                return $value ? 0 : 1;
            },
            'type' => Type::id(),
        ], ['upsertSku']);

        self::assertEquals(Utils::nowdoc('
            schema {
              query: UpsertSkuError
            }
            
            "User errors for UpsertSku"
            type UpsertSkuError {
              "A numeric error code. 0 on success, non-zero on failure."
              code: Int
            
              "An error message."
              msg: String
            }

        '), SchemaPrinter::doPrint(new Schema(['query' => $type])));
    }
}
