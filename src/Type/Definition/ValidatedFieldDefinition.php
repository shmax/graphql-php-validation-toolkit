<?php declare(strict_types=1);

namespace GraphQL\Type\Definition;

use GraphQL\Executor\Executor;
use GraphQL\Language\AST\InputValueDefinitionNode;

/**
 * @phpstan-import-type ArgumentType from InputObjectField
 * @phpstan-import-type InputObjectConfig from InputObjectType
 * @phpstan-import-type UnnamedArgumentConfig from Argument
 * @phpstan-import-type FieldResolver from Executor
 * @phpstan-type ValidatedFieldConfig array{
 *   typeSetter?: callable,
 *   name?: string,
 *   validName?: string,
 *   resultName?: string,
 *   args: array<UnnamedArgumentConfig>,
 *   resolve?: FieldResolver|null,
 *   validate?: callable(mixed $value): mixed,
 *   errorCodes?: array<string>,
 *   type: Type
 * }
 */
class ValidatedFieldDefinition extends FieldDefinition
{
    /** @var callable */
    protected $typeSetter;

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
            'type' => fn () => static::_createUserErrorsType($name, $args, $config),
            'args' => $args,
            'name' => $name,
            'resolve' => function ($value, $args1, $context, $info) use ($config, $args) {
                // validate inputs
                $config['type'] = new InputObjectType([
                    'name' => '',
                    'fields' => $args,
                ]);
                $config['isRoot'] = true;

                $errors = $this->_validate($config, $args1);
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
    protected function _createUserErrorsType(string $name, array $args, array $config): UserErrorsType
    {
        return UserErrorsType::create([
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
    }

    private function _noop(mixed $value): int
    {
        // this is just a no-op validation function to fallback to when no validation function is provided
        return 0;
    }

    /**
     * @param   mixed[] $arr
     */
    protected function _isAssoc(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }

        return \array_keys($arr) !== \range(0, \count($arr) - 1);
    }

    /**
     * @param array<string, mixed> $config
     * @param   mixed[]  $value
     * @param   Array<string|int> $path
     *
     * @throws  ValidateItemsError
     */
    protected function _validateItems(array $config, array $value, array $path, callable $validate): void
    {
        foreach ($value as $idx => $subValue) {
            if (\is_array($subValue) && ! $this->_isAssoc($subValue)) {
                $path[\count($path) - 1] = $idx;
                $newPath = $path;
                $newPath[] = 0;
                $this->_validateItems($config, $subValue, $newPath, $validate);
            } else {
                $path[\count($path) - 1] = $idx;
                $err = $validate($subValue);

                if (empty($err)) {
                    $wrappedType = $config['type']->getInnermostType();
                    $err = $this->_validate([
                        'type' => $wrappedType,
                    ], $subValue, $config['type'] instanceof ListOfType);
                }

                if ($err) {
                    throw new ValidateItemsError($path, $err);
                }
            }
        }
    }

    /**
     * @param mixed[] $arg
     * @param mixed $value
     *
     * @return mixed[]
     */
    protected function _validate(array $arg, $value, bool $isParentList = false): array
    {
        $res = [];

        if (\is_callable($arg['type'])) {
            $arg['type'] = $arg['type']();
        }

        $type = $arg['type'];
        switch ($type) {
            case $type instanceof ListOfType:
                $this->_validateListOfType($arg, $value, $res);
                break;

            case $type instanceof NonNull:
                $arg['type'] = $type->getWrappedType();
                $res = $this->_validate($arg, $value);
                break;

            case $type instanceof InputObjectType:
                $this->_validateInputObject($arg, $value, $res, $isParentList);
                break;

            default:
                if (\is_callable($arg['validate'] ?? null)) {
                    $res['error'] = $arg['validate']($value) ?? [];
                }
        }

        return \array_filter($res);
    }

    /**
     * @param array<string, mixed> $config
     * @param array<mixed> $res
     */
    protected function _validateListOfType(array $config, mixed $value, array &$res): void
    {
        try {
            $this->_validateItems($config, $value, [0], $config['validate'] ?? [$this, '_noop']);
        } catch (ValidateItemsError $e) {
            if (isset($e->error['suberrors'])) {
                $err = $e->error;
            } else {
                $err = [
                    'error' => $e->error,
                ];
            }
            $err['path'] = $e->path;
            $res[] = $err;
        }
    }

    /**
     * @phpstan-param ValidatedFieldConfig $arg
     * @param array<mixed> $res
     */
    protected function _validateInputObject(mixed $arg, mixed $value, array &$res, bool $isParentList): void
    {
        /**
         * @phpstan-var InputObjectType
         */
        $type = $arg['type'];
        if (isset($arg['validate'])) {
            $err = $arg['validate']($value) ?? [];
            if ($err != 0) {
                $res['error'] = $err;

                return;
            }
        }

        $this->_validateInputObjectFields($type, $arg, $value, $res, $isParentList);
    }

    /**
     * @phpstan-param InputObjectType $type
     * @phpstan-param  ValidatedFieldConfig $config
     * @param array<mixed> $res
     */
    protected function _validateInputObjectFields(InputObjectType $type, array $config, mixed $value, array &$res, bool $isParentList = false): void
    {
        $createSubErrors = UserErrorsType::needSuberrors($config, $isParentList);

        $fields = $type->getFields();
        if (\is_array($value)) {
            foreach ($value as $key => $subValue) {
                $config = $fields[$key]->config;
                $error = $this->_validate($config, $subValue);

                if (! empty($error)) {
                    $createSubErrors ? $res[UserErrorsType::SUBERRORS_NAME][$key] = $error : $res[$key] = $error;
                }
            }
        }
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
