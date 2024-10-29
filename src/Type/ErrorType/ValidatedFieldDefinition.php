<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Type\ErrorType;

use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * @phpstan-import-type ArgumentType from InputObjectField
 * @phpstan-import-type UnnamedArgumentConfig from Argument
 * @phpstan-import-type FieldResolver from Executor
 * @phpstan-type ValidationSettings array{
 *    typeSetter?: callable,
 *    args: array<UnnamedArgumentConfig>,
 *    validName?: string,
 *    resultName?: string,
 *    validationMode?: "partial"|"full",
 *    name?: string,
 *    required?: bool|array<int,string>|callable(): bool|array<int|string>,
 *    resolve?: FieldResolver|null,
 *    validate?: callable(mixed $value): mixed,
 *    errorCodes?: class-string<\UnitEnum>|null,
 *    type: Type
 *  }
 * @phpstan-type ValidatedFieldDefinitionConfig array{
 *   name?: string,
 *   required?: bool|array<int,string>|callable(): bool|array<int|string>,
 *   resolve?: FieldResolver|null,
 *   validate?: callable(mixed $value): mixed,
 *   errorCodes?: class-string<\UnitEnum>|null,
 *   type: Type
 * }
 */
class ValidatedFieldDefinition extends FieldDefinition
{
    protected ValidationErrorType $userErrorsType;

    protected string $validFieldName;

    protected string $resultFieldName;

    /**
     * @param ValidationSettings $field
     */
    public function __construct($field)
    {
        $args = $field['args'];
        $name = $field['name'] ?? \lcfirst($this->tryInferName());

        $this->validFieldName = $field['validName'] ?? '_valid';
        $this->resultFieldName = $field['resultName'] ?? '_result';


        parent::__construct(array_merge([
            'validationMode' => $field['validationMode'] ?? 'full'
        ], [
            'type' => fn() => $this->userErrorsType = static::_createUserErrorsType($name, $field),
            'args' => $args,
            'name' => $name,
            'resolve' => function ($value, $args1, $context, $info) use ($field, $args) {
                // validate inputs
                $field['type'] = new InputObjectType([
                    'name' => '',
                    'fields' => $args,
                ]);

                $result = $errors = $this->userErrorsType->validate($field, $args1, $this->config);
                $result[$this->validFieldName] = empty($errors);

                if (!empty($result[$this->validFieldName])) {
                    $result[$this->resultFieldName] = $field['resolve']($value, $args1, $context, $info);
                }

                return $result;
            },
        ]));
    }

    /**
     * @phpstan-param ValidationSettings $settings
     */
    protected function _createUserErrorsType(string $name, array $settings): ValidationErrorType
    {
        $args = $settings['args'];
        $validationErrorType = ValidationErrorType::create([
            'errorCodes' => $settings['errorCodes'] ?? null,
            'fields' => [
                $this->resultFieldName => [
                    'type' => $settings['type'],
                    'description' => 'The payload, if any',
                    'resolve' => fn($value) => $value[$this->resultFieldName] ?? null
                ],
                $this->validFieldName => [
                    'type' => Type::nonNull(Type::boolean()),
                    'description' => 'Whether all validation passed. True for yes, false for no.',
                    'resolve' => function ($value) {
                        return $value[$this->validFieldName];
                    },
                ],
            ],
            'validate' => $settings['validate'] ?? null,
            'type' => new InputObjectType([
                'fields' => $args,
                'name' => '',
            ]),
        ], [$name]);

        $validationErrorType->name = \ucfirst($name) . 'Result';
        return $validationErrorType;
    }

    /**
     * @return mixed|string|string[]|null
     * @throws \ReflectionException
     *
     */
    protected function tryInferName()
    {
        // If class is extended - infer name from className
        // QueryType -> Type
        // SomeOtherType -> SomeOther
        $tmp = new \ReflectionClass($this);
        $name = $tmp->getShortName();

        return \preg_replace('~Type$~', '', $name);
    }
}
