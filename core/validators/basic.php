<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 30.11.14
 * Time: 4:58
 */

function _baseValidatorFunctionRegexp($value, $params)
{
    $result = true;
    $regexpPatern = Converters::convert($params, 'string');
    if (!preg_match($regexpPatern, $value)) {
        $result = "value '$value' not math regexp patern '$regexpPatern'";
    }
    return $result;
}

function _baseValidatorFunctionString($value, $params)
{
    $result = true;
    $convertToStrValus = Converters::convert($value, 'string');
    if ($value != $convertToStrValus) {
        throw new Exception('not string');
    }
    $parseLen = Converters::convert($params, array(
            'trim',
            array('parseRegexp', '#^len\s*(<|>|<=|>=|=)\s*(\d+)$#', 'operator, len')
        )
    );
    if ($parseLen) {//Ограничение по длинне
        $operator = $parseLen['operator'];
        $len = $parseLen['len'];
        switch ($operator) {
            case '<':
                if (strlen($value) >= $len) {
                    throw new Exception("length string not < $len");
                }
                break;
            case '>':
                if (strlen($value) <= $len) {
                    throw new Exception("length string not > $len");
                }
                break;
            case '<=':
                if (strlen($value) > $len) {
                    throw new Exception("length string not <= $len");
                }
                break;
            case '>=':
                if (strlen($value) < $len) {
                    throw new Exception("length string not >= $len");
                }
                break;
            case '=':
                if (strlen($value) != $len) {
                    throw new Exception("length string not = $len");
                }
                break;
        }
    }
    return $result;
}

function _baseValidatorFunctionInteger($value, $params)
{
    $result = true;
    $intVal = Converters::convert($value,'integer');
    if($intVal!=$value){
        throw new Exception('not integer');
    }
    $isUnsigned = Converters::convert($params, 'parseRegexp', '#^(unsigned)$#');
    if($isUnsigned && $value<0){
        throw new Exception('not unsigned');
    }
    return $result;
}


$registerValidators = array(
    'regexp' => '_baseValidatorFunctionRegexp',
    'string' => '_baseValidatorFunctionString',
    'integer' => '_baseValidatorFunctionInteger',
);

foreach ($registerValidators as $validatorName => $validatorFn) {
    Validator::registerRule($validatorName, $validatorFn);
}