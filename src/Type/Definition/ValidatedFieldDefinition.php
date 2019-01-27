<?php

declare(strict_types=1);

namespace GraphQL\Type\Definition;

use Exception;
use ReflectionClass;
use ReflectionException;
use function array_filter;
use function is_callable;
use function lcfirst;
use function preg_replace;
use function ucfirst;

class ValidatedFieldDefinition extends FieldDefinition
{
    /** @var callable */
    protected $typeSetter;

    /**
     * @param mixed[] $config
     */
    public function __construct($config)
    {
        $args = $config['args'];
        $name = $config['name'] ?? lcfirst($this->tryInferName());

        $this->typeSetter = $config['typeSetter'] ?? null;

        if (! isset($config['type'])) {
            throw new Exception('You must specify a type for your field');
        }

        $type = UserErrorsType::create([
            'errorCodes' => $config['errorCodes'] ?? null,
            'fields' => [
                'result' => [
                    'type' => $config['type'],
                    'description' => 'The payload, if any',
                    'resolve' => static function ($value) {
                        return $value['result'] ?? null;
                    },
                ],
                'valid' => [
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
            'resolve' => function ($value, $args1, $context, $info) use ($config, $args) {
                // validate inputs
                $config['type']  = new InputObjectType([
                    'name'=>'',
                    'fields' => $args,
                ]);
                $errors          = $this->_validate($config, $args1);
                $result          = $errors;
                $result['valid'] = ! $errors;

                $innerRes = null;

                if (! isset($result['error']) && ! isset($result['suberrors'])) {
                    $result['result'] = $config['resolve']($value, $args1, $context, $info);
                }

                return $result;
            },
        ]);
    }

    /**
     * @return mixed[]
     */
    protected function _validate($arg, $value)
    {
        $res = [];

        $type = $arg['type'];
        switch ($type) {
            case $type instanceof ListOfType:
                if (isset($arg['validate'])) {
                    $err = $arg['validate']($value) ?? [];
                    if ($err) {
                        $res['error'] = $err;
                        break;
                    }
                }

                foreach ($value as $idx => $subValue) {
                    if (isset($arg['validateItem'])) {
                        $err = $arg['validateItem']($subValue);
                    } else {
                        $config         = $type->ofType->config;
                        $config['type'] = $type->ofType;
                        $err            = $this->_validate($config, $subValue);
                    }

                    if (! $err) {
                        continue;
                    }

                    $err['index'] = $idx;
                    $suberrors    = null;
                    if (isset($err['suberrors'])) {
                        $suberrors = $err['suberrors'];
                        unset($err['suberrors']);
                    }
                    $res['suberrors'][] = [
                        'suberrors' => $suberrors,
                        'error' => $err,
                        'index' => $idx,
                    ];
                }
                break;

            case $type instanceof NonNull:
                $arg['type'] = $type->getWrappedType();
                $res         = $this->_validate($arg, $value);
                break;

            case $type instanceof InputObjectType:
                if ($arg['validate'] ?? null) {
                    $err = $arg['validate']($value) ?? [];
                    if ($err) {
                        $res['error'] = $err;
                        break;
                    }
                }

                $fields = $type->getFields();
                foreach ($value as $key => $subValue) {
                    $config                 = $fields[$key]->config;
                    $res['suberrors'][$key] = $this->_validate($config, $subValue);
                }
                $res['suberrors'] = array_filter($res['suberrors']);
                break;

            default:
                if ($value === null) {
                    break;
                }
                if (isset($arg['validate']) && is_callable($arg['validate'])) {
                    $res['error'] = $arg['validate']($value) ?? [];
                }
        }

        return array_filter($res);
    }

    /**
     * @return mixed|string|string[]|null
     *
     * @throws ReflectionException
     */
    protected function tryInferName()
    {
        if ($this->name) {
            return $this->name;
        }

        // If class is extended - infer name from className
        // QueryType -> Type
        // SomeOtherType -> SomeOther
        $tmp  = new ReflectionClass($this);
        $name = $tmp->getShortName();

        return preg_replace('~Type$~', '', $name);
    }
}
