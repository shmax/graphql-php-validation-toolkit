<?php

declare(strict_types=1);

namespace GraphQL\Type\Definition;

use GraphQL;
use function array_map;
use function array_merge;
use function count;
use function gettype;
use function implode;
use function is_callable;
use function ucfirst;

class UserErrorsType extends ObjectType
{
    /**
     * @param mixed[]  $config
     * @param string[] $path
     */
    public function __construct(array $config, array $path, $isParentList = false)
    {
        $finalFields = $config['fields'] ?? [];

        GraphQL\Utils\Utils::invariant($config['type'] instanceof Type, 'Must provide type.');

        if (isset($config['errorCodes'])) {
            /** code property */
            $finalFields['code'] = [
                'type' => $this->_set(new EnumType([
                    'name' => $this->_nameFromPath(array_merge($path)) . 'ErrorCode',
                    'description' => 'Error code',
                    'values' => $config['errorCodes'],
                ]), $config),
                'description' => 'An error code',
                'resolve' => static function ($value) {
                    return $value['error'][0] ?? null;
                },
            ];

            /**
             * msg property
             */
            $finalFields['msg'] = [
                'type' => Type::string(),
                'description' => 'A natural language description of the issue',
                'resolve' => static function ($value) {
                    return $value['error'][1] ?? null;
                },
            ];
        }

        $type = $this->_getType($config['type']);
        if ($type instanceof InputObjectType) {
            $fields = [];
            foreach ($type->getFields() as $key => $field) {
                $newType = static::create(
                    $field->config + ['typeSetter' => $config['typeSetter'] ?? null],
                    array_merge($path, [$key])
                );

                if (! $newType) {
                    continue;
                }

                $fields[$key] = [
                    'description' => 'Error for ' . $key,
                    'type' => $newType,
                    'resolve' => static function ($value) use ($key) {
                        return $value[$key] ?? null;
                    },
                ];
            }

            if ($fields) {
                /**
                 * suberrors property
                 */
                $finalFields['suberrors'] = [
                    'type' => $this->_set(new ObjectType([
                        'name' => $this->_nameFromPath(array_merge($path, ['suberrors'])),
                        'description' => 'User Error',
                        'fields' => $fields,
                    ]), $config),
                    'description' => 'Suberrors for ' . ucfirst($path[count($path)-1]),
                    'resolve' => static function (array $value) {
                        return $value['suberrors'] ?? null;
                    },
                ];
            }
        } elseif ($type instanceof ListOfType) {
            if (isset($config['validateItem'])) {
                $newType = static::create(
                    [
                        'type' => $type->ofType,
                        'validate' => $config['validateItem'],
                        'errorCodes' => $config['suberrorCodes'] ?? null,
                        'typeSetter' => $config['typeSetter'] ?? null,
                    ],
                    array_merge($path, [$type->ofType->name]),
                    true
                );
            } else {
                $newType = static::create(
                    [
                        'type'=>$type->ofType,
                        'typeSetter' => $config['typeSetter'] ?? null,
                    ],
                    array_merge($path, [$type->ofType->name]),
                    true
                );
            }

            if ($newType) {
                $finalFields['suberrors'] = [
                    'description' => 'Suberrors for the list of ' . $type->ofType . ' items',
                    'type' => Type::listOf($newType),
                    'resolve' => static function ($value) {
                        return $value['suberrors'] ?? null;
                    },
                ];
            }
        }

        if ($isParentList && (isset($finalFields['suberrors']) || isset($finalFields['code']))) {
            /**
             * index property
             */
            $finalFields['index'] = [
                'type' => Type::int(),
                'description' => 'The index of the array item this error is paired with',
                'resolve' => static function ($value) {
                    return $value['index'];
                },
            ];
        }

        parent::__construct([
            'name' => $this->_nameFromPath(array_merge($path)) . ucfirst('error'),
            'description' => 'User errors for ' . ucfirst($path[count($path)-1]),
            'fields' => $finalFields,
        ]);
    }

    /**
     * @param mixed[] $config
     *
     * @return mixed
     */
    protected function _set(Type $type, array $config)
    {
        if (is_callable($config['typeSetter'] ?? null)) {
            $config['typeSetter']($type);
        }
        return $type;
    }

    /**
     * @param mixed[]  $config
     * @param string[] $path
     */
    public static function create(array $config, array $path, $isParentList = false, $name = '') : ?self
    {
        $config['fields'] = $config['fields'] ?? [];
        if (isset($config['validate']) && is_callable($config['validate'])) {
            $config['fields']['code'] = $config['fields']['code'] ?? [
                'type' => Type::int(),
                'description' => 'A numeric error code. 0 on success, non-zero on failure.',
                'resolve' => static function ($value) {
                    $error = $value['error'] ?? null;
                    switch (gettype($error)) {
                        case 'integer':
                            return $error;
                    }
                    return $error[0];
                },
            ];

            $config['fields']['msg'] = $config['fields']['msg'] ?? [
                'type' => Type::string(),
                'description' => 'An error message.',
                'resolve' => static function ($value) {
                    $error = $value['error'] ?? null;
                    switch (gettype($error)) {
                        case 'integer':
                            return '';
                    }
                    return $error[1];
                },
            ];
        }

        $userErrorType = new static($config, $path, $isParentList);
        if ($userErrorType->getFields()) {
            $userErrorType->name = $name ?: $userErrorType->name;
            if (isset($config['typeSetter']) && is_callable($config['typeSetter'])) {
                $config['typeSetter']($userErrorType);
            }
            return $userErrorType;
        }
        return null;
    }

    /**
     * @param string[] $path
     */
    protected function _nameFromPath(array $path) : string
    {
        return implode('_', array_map('ucfirst', $path));
    }

    protected function _getType(Type $type)
    {
        if ($type instanceof NonNull) {
            $type = $type->getWrappedType();
        }
        return $type;
    }
}
