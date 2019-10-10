<?php

declare(strict_types=1);

namespace GraphQL\Type\Definition;

use Exception;
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
    protected const ERROR_NAME = 'suberrors';
    protected const CODE_NAME = 'code';
    protected const MESSAGE_NAME = 'msg';

    /**
     * @param mixed[]  $config
     * @param string[] $path
     */
    public function __construct(array $config, array $path, bool $isParentList = false)
    {
        $finalFields = $config['fields'] ?? [];

        if (!isset($config['type'])) {
            throw new Exception('You must specify a type for your field');
        }

        GraphQL\Utils\Utils::invariant($config['type'] instanceof Type, 'Must provide type.');

        $this->_addErrorCodes($config, $finalFields, $path);

        $type = $this->_getType($config);
        if ($type instanceof InputObjectType) {
            $this->_buildInputObjectType($type, $config, $path, $finalFields, $isParentList);
        }

        if ($isParentList) {
            $this->_addPath($finalFields);
        }

        parent::__construct([
            'name' => $this->_nameFromPath(array_merge($path)) . ucfirst('error'),
            'description' => 'User errors for ' . ucfirst($path[count($path)-1]),
            'fields' => $finalFields,
        ]);
    }

    protected function _getType($config) {
        $type = $config['type'];
        if ($type instanceof NonNull || $type instanceof ListOfType) {
            $type = $type->getWrappedType(true);
        }
        return $type;
    }

    protected function _buildInputObjectType(InputObjectType $type, $config, $path, &$finalFields, $isParentList) {
        $createSubErrors = !empty($config['validate']) || !empty($config['isRoot']) || $isParentList;
        $fields = [];
        foreach ($type->getFields() as $key => $field) {
            $fieldType = $this->_getType($field->config);
            $newType = static::create(
                [
                    'validate' => $field->config['validate'] ?? null,
                    'errorCodes' => $field->config['errorCodes'] ?? null,
                    'type' => $fieldType,
                    'typeSetter' => $config['typeSetter'] ?? null
                ],
                array_merge($path, [$key]),
                $field->type instanceof ListOfType
            );

            if (!$newType) {
                continue;
            }

            $errType = [
                'description' => 'Error for ' . $key,
                'type' => $field->type instanceof ListOfType ? Type::listOf($newType) : $newType,
                'resolve' => static function ($value) use ($key) {
                    return $value[$key] ?? null;
                },
            ];

            if($createSubErrors) {
                $fields[$key] = $errType;
            }
            else {
                $finalFields[$key] = $errType;
            }
        }

        if ($createSubErrors && !empty($fields)) {
            /**
             * errors property
             */
            $finalFields[static::ERROR_NAME] = [
                'type' => $this->_set(new ObjectType([
                    'name' => $this->_nameFromPath(array_merge($path, ['fieldErrors'])),
                    'description' => 'User Error',
                    'fields' => $fields,
                ]), $config),
                'description' => 'Validation errors for ' . ucfirst($path[count($path)-1]),
                'resolve' => static function (array $value) {
                    return $value['errors'] ?? null;
                },
            ];
        }
    }

    protected function _addErrorCodes($config, &$finalFields, $path) {
        if (isset($config['errorCodes'])) {
            if (!isset($config['validate'])) {
                throw new Exception('If you specify errorCodes, you must also provide a validate callback');
            }

            /** code property */
            $finalFields[static::CODE_NAME] = [
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
            $finalFields[static::MESSAGE_NAME] = [
                'type' => Type::string(),
                'description' => 'A natural language description of the issue',
                'resolve' => static function ($value) {
                    return $value['error'][1] ?? null;
                },
            ];
        }
    }

    protected function _addPath(&$finalFields) {
        if (!empty($finalFields['code']) || !empty($finalFields['suberrors'])) {
            $finalFields['path'] = [
                'type' => Type::listOf(Type::int()),
                'description' => 'A path describing this items\'s location in the nested array',
                'resolve' => static function ($value) {
                    return $value['path'];
                },
            ];
        }
    }

    /**
     * @param Type $type
     * @param mixed[] $config
     * @return Type
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
     * @param string   $name
     *
     * @return static|null
     *
     * @throws Exception
     */
    public static function create(array $config, array $path, bool $isParentList = false, string $name = '') : ?self
    {
        $config['fields'] = $config['fields'] ?? [];
        if (is_callable($config['validate'] ?? null)) {
            $config['fields'][static::CODE_NAME] = $config['fields'][static::CODE_NAME] ?? static::_generateIntCodeType();
            $config['fields'][static::MESSAGE_NAME] = $config['fields'][static::MESSAGE_NAME] ?? static::_generateMessageType();
        }

        $userErrorType = new static($config, $path, $isParentList);
        if ($userErrorType->getFields()) {
            $userErrorType->name = $name ?: $userErrorType->name;
            if (is_callable($config['typeSetter'] ?? null)) {
                $config['typeSetter']($userErrorType);
            }
            return $userErrorType;
        }
        return null;
    }

    protected static function _generateIntCodeType() {
        return [
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
    }

    protected static function _generateMessageType() {
        return [
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

    /**
     * @param string[] $path
     */
    protected function _nameFromPath(array $path) : string
    {
        return implode('_', array_map('ucfirst', $path));
    }
}
