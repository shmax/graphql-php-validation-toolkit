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

        $this->validFieldName = $config['validName'] ?? 'valid';
        $this->resultFieldName = $config['resultName'] ?? 'result';


        parent::__construct([
            'type' => fn () => $this->userErrorsType = static::_createUserErrorsType($name, $args, $config),
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

                if (! empty($result['valid'])) {
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
                    'resolve' => static function ($value) {
                        return $value['valid'];
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
     * @param mixed[] $arg
     * @param mixed $value
     *
     * @return mixed[]
     */
    protected function _validate(array $arg, $value): array
    {
        $res = [];

        if (\is_callable($arg['type'])) {
            $arg['type'] = $arg['type']();
        }

        $type = $arg['type'];
        switch ($type) {
            case $type instanceof ListOfErrorType:
                $this->_validateListOfType($arg, $value, $res);
                break;

            case $type instanceof NonNull:
                $arg['type'] = $type->getWrappedType();
                $res = $this->_validate($arg, $value);
                break;

            case $type instanceof InputObjectType:
                $this->_validateInputObject($arg, $value);
                break;

            default:
                if (\is_callable($arg['validate'] ?? null)) {
                    $res['error'] = $arg['validate']($value) ?? [];
                }
        }

        return \array_filter($res);
    }

    /**
     * @param   array<string, mixed> $config
     * @param   mixed[]  $value
     * @param   array<mixed> $res
     * @param   Array<string|int> $path
     */
    protected function _validateListOfType(array $config, array $value, array &$res, array $path=[0]): void
    {
        $validate = $config['validate'] ?? null;
        $wrappedType = $config['type']->getWrappedType();
        foreach ($value as $idx => $subValue) {
            $path[\count($path) - 1] = $idx;
            if ($wrappedType instanceof ListOfErrorType) {
                $newPath = $path;
                $newPath[] = 0;
                $this->_validateListOfType(["type"=>$wrappedType, "validate" => $validate], $subValue, $res, $newPath );
            } else {
                $err = $validate != null ? $validate($subValue): 0;

                if (empty($err)) {
                    $wrappedType = $config['type']->getInnermostType();
                    $err = $this->_validate([
                        'type' => $wrappedType,
                    ], $subValue, $config['type'] instanceof ListOfErrorType);
                }

                if ($err) {
                    if (isset($err['suberrors'])) {
                        $err = $err;
                    } else {
                        $err = [
                            'error' => $err,
                        ];
                    }
                    $err['path'] = $path;
                    $res[] = $err;
                }
            }
        }
    }

    /**
     * @phpstan-param ValidatedFieldConfig $arg
     * @param array<mixed> $res
     */
    protected function _validateInputObject(mixed $arg, mixed $value, array &$res): void
    {
        /**
         * @phpstan-var InputObjectErrorType
         */
        $type = $arg['type'];
        if (isset($arg['validate'])) {
            $err = $arg['validate']($value) ?? [];
            $res['error'] = $err;
        }

        $this->_validateInputObjectFields($type, $arg, $value, $res);
    }

    /**
     * @phpstan-param InputObjectErrorType $type
     * @phpstan-param  ValidatedFieldConfig $objectConfig
     * @param array<mixed> $res
     */
    protected function _validateInputObjectFields(InputObjectType $type, array $objectConfig, mixed $value, array &$res): void
    {
//        $createSubErrors = ErrorType::needSuberrors($objectConfig);
//
//        $fields = $type->getFields();
//        foreach ($fields as $key => $field) {
//            $error = null;
//            $config = $field->config;
//
//            $isKeyPresent = array_key_exists($key, $value);
//            $isRequired = $config['required'] ?? false;
//            if(is_callable($isRequired)) {
//                $isRequired = $isRequired();
//            }
//            if($isRequired && !isset($value[$key])) {
//                if ($isRequired === true) {
//                    $error = ['error' => [1, "$key is required"]];
//                }
//                else if (is_array($isRequired)) {
//                    $error = ['error' => $isRequired];
//                }
//            }
//            else if ($isKeyPresent) {
//                $error = $this->_validate($config, $value[$key] ?? null);
//            }
//
//            if (!empty($error)) {
//                if ($createSubErrors) {
//                    $res[ErrorType::FIELDS_NAME][$key] = $error;
//                } else {
//                    $res[$key] = $error;
//                }
//            }
//        }
    }

    /**
     * @throws \ReflectionException
     *
     * @return mixed|string|string[]|null
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
