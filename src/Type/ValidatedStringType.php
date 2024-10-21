<?php
namespace GraphQlPhpValidationToolkit\Type;

use GraphQL\Type\Definition\StringType;

class ValidatedStringType extends StringType {
public function __construct(array $config = [])
    {
        $config['name'] = 'ValidatedString';
        parent::__construct($config);
    }
}