<?php

declare(strict_types=1);

namespace GraphQL\Type\Definition;

use PHPStan\Type\CompoundType;
use ReflectionClass;
use ReflectionException;
use function array_filter;
use function array_keys;
use function count;
use function is_array;
use function is_callable;
use function lcfirst;
use function preg_replace;
use function range;
use function ucfirst;

class ValidatedFieldDefinition extends FieldDefinition
{
    /** @var callable */
    protected $typeSetter;

    /**
     * @param mixed[] $config
     */
    public function __construct(array $config)
    {
        $args = $config['args'];
        $name = $config['name'] ?? lcfirst($this->tryInferName());
        $this->typeSetter = $config['typeSetter'] ?? null;

        $validFieldName = $config['validName'] ?? 'valid';
        $resultFieldName = $config['resultName'] ?? 'result';

        $type = UserErrorsType::create([
            'errorCodes' => $config['errorCodes'] ?? null,
            'isRoot' => true,
            'fields' => [
                $resultFieldName => [
                    'type' => $config['type'],
                    'description' => 'The payload, if any',
                    'resolve' => static function ($value) {
                        return $value['result'] ?? null;
                    },
                ],
                $validFieldName => [
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
            'typeSetter' => $this->typeSetter,
        ], [$name], false, ucfirst($name) . 'Result');

        parent::__construct([
            'type' => $type,
            'args' => $args,
            'name' => $name,
            'resolve' => function ($value, $args1, $context, $info) use ($config, $args, $validFieldName, $resultFieldName) {
                // validate inputs
                $config['type']  = new InputObjectType([
                    'name'=>'',
                    'fields' => $args,
                ]);
                $errors          = $this->_validate($config, $args1);
                $result          = $errors;
                $result[$validFieldName] = !$errors;

                if (!empty($result['valid'])) {
                    $result[$resultFieldName] = $config['resolve']($value, $args1, $context, $info);
                }

                return $result;
            },
        ]);
    }

    /**
     * @param   mixed[] $arr
     */
    protected function _isAssoc(array $arr) : bool
    {
        if ($arr === []) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * @param   mixed[]  $value
     * @param   string[] $path
     *
     * @throws  ValidateItemsError
     */
    protected function _validateItems($config, array $value, array $path, callable $validate) : void
    {
        foreach ($value as $idx => $subValue) {
            if (is_array($subValue) && !$this->_isAssoc($subValue)) {
                $path[count($path)-1] = $idx;
                $newPath              = $path;
                $newPath[]            = 0;
                $this->_validateItems($config, $subValue, $newPath, $validate);
            } else {
                $path[count($path) - 1] = $idx;
                $err                    = $validate($subValue);

                if(empty($err)) {
                    $wrappedType = $config['type']->getWrappedType(true);
                    $err = $this->_validate([
                        'type' => $wrappedType
                    ], $subValue);

                    $err = $err['errors'] ?? null;
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
     * @return mixed[]
     */
    protected function _validate(array $arg, $value) : array
    {
        $res = [];

        $type = $arg['type'];
        switch ($type) {
            case $type instanceof ListOfType:
                $this->_validateListOfType($arg, $value, $res);
                break;

            case $type instanceof NonNull:
                $arg['type'] = $type->getWrappedType();
                $res         = $this->_validate($arg, $value);
                break;

            case $type instanceof InputObjectType:
                $this->_validateInputObjectType($arg, $value, $res);
                break;

            default:
                if (is_callable($arg['validate'] ?? null)) {
                    $res['error'] = $arg['validate']($value) ?? [];
                }
        }

        return array_filter($res);
    }

    protected function _validateListOfType($config, $value, &$res) {
        if (isset($config['validate'])) {
            try {
                $this->_validateItems($config, $value, [0], $config['validate']);
            } catch (ValidateItemsError $e) {
                $res['errors'] = [
                    'error' => $e->error,
                    'path' => $e->path,
                ];
            }
        }
    }

    protected function _validateInputObjectType($arg, $value, &$res) {
        $type = $arg['type'];
        if (isset($arg['validate'])) {
            $err = $arg['validate']($value) ?? [];
            if ($err) {
                $res['error'] = $err;
                return;
            }
        }

        $this->_validateFields($type, $value, $res);
    }

    protected function _validateFields($type, $value, &$res) {
        $fields = $type->getFields();
        if (is_array($value)) {
            foreach ($value as $key => $subValue) {
                $config                 = $fields[$key]->config;
                $res['errors'][$key] = $this->_validate($config, $subValue);
            }
            $res['errors'] = array_filter($res['errors'] ?? []);
        }
    }

    /**
     * @return mixed|string|string[]|null
     *
     * @throws ReflectionException
     */
    protected function tryInferName()
    {
        // If class is extended - infer name from className
        // QueryType -> Type
        // SomeOtherType -> SomeOther
        $tmp  = new ReflectionClass($this);
        $name = $tmp->getShortName();

        return preg_replace('~Type$~', '', $name);
    }
}
