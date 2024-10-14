<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Type\UserErrorType;

use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * @phpstan-import-type ArgumentType from InputObjectField
 * @phpstan-import-type InputObjectConfig from InputObjectErrorType
 * @phpstan-import-type UnnamedArgumentConfig from Argument
 * @phpstan-import-type FieldResolver from Executor
 * @phpstan-type ValidatedFieldConfig array{
 *   typeSetter?: callable,
 *   name?: string,
 *   validName?: string,
 *   required?: bool|array<int,string>|callable(): bool|array<int|string>,
 *   resultName?: string,
 *   args: array<UnnamedArgumentConfig>,
 *   resolve?: FieldResolver|null,
 *   validate?: callable(mixed $value): mixed,
 *   errorCodes?: class-string<\UnitEnum>|null,
 *   type: Type
 * }
 */
class ValidatedFieldDefinition extends FieldDefinition
{
    /** @var callable */
    protected $typeSetter;

    protected ErrorType $userErrorsType;

    protected string $validFieldName;

    protected string $resultFieldName;

    /**
     * @phpstan-param ValidatedFieldConfig $config
     */
    public function __construct(array $config)
    {
        $args = $config['args'];
        $name = $config['name'] ?? \lcfirst($this->tryInferName());

        $this->validFieldName = $config['validName'] ?? '__valid';
        $this->resultFieldName = $config['resultName'] ?? '__result';


        parent::__construct([
            'type' => fn() => $this->userErrorsType = static::_createUserErrorsType($name, $args, $config),
            'args' => $args,
            'name' => $name,
            'resolve' => function ($value, $args1, $context, $info) use ($config, $args) {
                // validate inputs
                $config['type'] = new InputObjectType([
                    'name' => '',
                    'fields' => $args,
                ]);
                $config['isRoot'] = true;

                $errors = $this->userErrorsType->validate($config, $args1);
                $result = $errors;
                $result[$this->validFieldName] = empty($errors);

                if (!empty($result['valid'])) {
                    $result[$this->resultFieldName] = $config['resolve']($value, $args1, $context, $info);
                }

                return $result;
            },
        ]);
    }

    /**
     * @phpstan-param array<UnnamedArgumentConfig> $args
     * @phpstan-param ValidatedFieldConfig $config
     */
    protected function _createUserErrorsType(string $name, array $args, array $config): ErrorType
    {
        $userErrorType = ErrorType::create([
            'errorCodes' => $config['errorCodes'] ?? null,
            'isRoot' => true,
            'fields' => [
                $this->resultFieldName => [
                    'type' => $config['type'],
                    'description' => 'The payload, if any',
                    'resolve' => static function ($value) {
                        return $value['result'] ?? null;
                    },
                ],
                $this->validFieldName => [
                    'type' => Type::nonNull(Type::boolean()),
                    'description' => 'Whether all validation passed. True for yes, false for no.',
                    'resolve' => function ($value) {
                        return $value[$this->validFieldName];
                    },
                ],
            ],
            'validate' => $config['validate'] ?? null,
            'type' => new InputObjectType([
                'fields' => $args,
                'name' => '',
            ]),
            'typeSetter' => $config['typeSetter'] ?? null,
        ], [$name], false, \ucfirst($name) . 'Result');

        $userErrorType->name = \ucfirst($name) . 'Result';
        return $userErrorType;
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
