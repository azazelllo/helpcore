<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 30.11.14
 * Time: 2:39
 */

function _basicConverterFunctionInteger($value, $params = null)
{
    $result = (int)$value;
    switch ($params) {
        case 'round':
            $result = round($value);
            break;
    }
    return $result;
}

function _baseConverterFunctionFloat($value, $params)
{
    $result = (float)$value;

    $parseMath = null;
    if (is_string($params)) {
        if (preg_match('#^round\s+(\d+)$#', $params, $parseMath)) {
            $round = $parseMath[1];
            $result = round($value, $round);
        } else if (preg_match('#^cut\s+(\d+)$#', $params, $parseMath)) {
            $cut = $parseMath[1];
            $v = (float)$value;
            $v = round($v * 10 * $cut);
            $result = $v / (10 * $cut);
        }
    }

    return $result;
}

function _baseConverterFunctionString($value, $params)
{
    if(!is_scalar($value)){
        $value = null;
    }
    $result = (string)$value;
    return $result;
}

function _baseConverterFunctionBool($value, $params)
{
    $result = !!$value;
    switch($params){
        case 'stringFalse':
            $result = ($value==='false' || $value==='FALSE')?false:!!$value;
            break;
    }
    return $result;
}

function _baseConverterFunctionDate($value, $params)
{
    $result = gmdate('Y-m-d H:i:s');
    preg_match('#^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})$#', $result, $parseMath);
    $Y = $parseMath[1];
    $m = $parseMath[2];
    $d = $parseMath[3];
    $H = $parseMath[4];
    $i = $parseMath[5];
    $s = $parseMath[6];
    $parseMath = null;
    if(preg_match('#^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})#', $value, $parseMath)){
        $Y = $parseMath[1];
        $m = $parseMath[2];
        $d = $parseMath[3];
        $H = $parseMath[4];
        $i = $parseMath[5];
        $s = $parseMath[6];
    }else if(preg_match('#^(\d{4})-(\d{2})-(\d{2})#', $value, $parseMath)){
        $Y = $parseMath[1];
        $m = $parseMath[2];
        $d = $parseMath[3];
    }else if(preg_match('#^\d+$#',$value)){
        $value = (int)$value;
        preg_match('#^(\d{4})-(\d{2})-(\d{2})#', gmdate('Y-m-d H:i:s', $value), $parseMath);
        $Y = $parseMath[1];
        $m = $parseMath[2];
        $d = $parseMath[3];
        $H = $parseMath[4];
        $i = $parseMath[5];
        $s = $parseMath[6];
    }

    if(!$params){
        $params = 'Y-m-d H:i:s';
    }
    $timeStamp = gmmktime($H, $i, $s, $m, $d, $Y);
    $result = gmdate($params, $timeStamp);
    return $result;
}

function _baseConverterFunctionJson($value, $params)
{
    $result = null;
    switch($params){
        case 'encode':
            $result = json_encode($value);
            break;
        case 'decode':
            $value = Converters::convert($value, 'string');
            $result = json_decode($value);
            break;
        case 'decode assoc':
            $result = json_decode($value, true);
            break;
    }
    return $result;
}

function _baseConverterFunctionExplode($value, $params)
{
    $delimiter = Converters::convert($params, 'string');
    $result = Converters::convert($value,'string');
    $result = explode($delimiter, $result);
    return $result;
}

function _baseConverterFunctionTrim($value, $params)
{
    $result = null;
    if(is_array($value)){
        $result = $value;
        foreach($result as &$curElement){
            $curElement = Converters::convert($curElement, 'trim');
        }
    }else{
        $result = Converters::convert($value,'string');
        $result = trim($result);
    }
    return $result;
}

function _baseConverterFunctionParseRegexp($value, $params)
{
    $result = null;
    $keys = null;
    $regexp = null;
    $parseMath = null;

    $prepareValue = Converters::convert($value,'string');

    if(is_string($params)){
        $regexp = $params;
    }else if(is_array($params)){
        if(isset($params[0])){
            $regexp = Converters::convert($params[0], 'string');
        }
        if(isset($params[1])){
            $keys = Converters::convert($params[1], array(
                'string',
                array('explode', ','),
                'trim'
            ));
        }
    }

    if($regexp && preg_match($regexp, $prepareValue, $parseMath)){
        $result = $parseMath[0];
        if($keys){
            unset($parseMath[0]);
            $result = array();
            foreach($keys as $num => $keyName){
                $result[$keyName] = isset($parseMath[$num+1])?$parseMath[$num+1]:null;
            }
        }
    }
    return $result;
}

function _baseConverterFunctionStrCase($value, $params)
{
    $result = $value;
    if(is_array($result)){
        foreach($result as &$curResultValue){
            $curResultValue = Converters::convert($curResultValue, 'strCase', $params);
        }
    }else{
        $result = Converters::convert($result, 'string');
        switch($params){
            case 'upper':
                $result = strtoupper($result);
                break;
            case 'lower':
                $result = strtolower($result);
                break;
        }
    }
    return $result;
}

function _baseConverterFunctionArrayElement($value, $params)
{
    $result = $value;
    switch($params){
        case 'last':
            $result = $value[count($value)-1];
            break;
    }
    return $result;
}

function _baseConverterFunctionImplode($value, $params)
{
    $delimiter = Converters::convert($params, 'string');
    $result = implode($delimiter, $value);
    return $result;
}


function _baseConverterFunctionCurlGetContent($value, $params)
{
    $url = $value;
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
    ));
    $out = curl_exec($ch);
    $error = curl_error($ch);
    $result = array(
        'content' => $out,
        'error' => $error,
    );
    return $result;
}

function _baseConverterFunctionParseUrlParams($value, $params){
    $result = $value;
    if(is_array($result)){
        foreach($result as &$item){
            $item = Converters::convert($item, 'parseUrlParams');
        }
    }else{
        $parseRes = null;
        parse_str($result, $parseRes);
        $result = $parseRes;
    }
    return $result;
}



$importConverters = array(
    'integer' => '_basicConverterFunctionInteger',
    'float' => '_baseConverterFunctionFloat',
    'string' => '_baseConverterFunctionString',
    'bool' => '_baseConverterFunctionBool',
    'date' => '_baseConverterFunctionDate',
    'json' => '_baseConverterFunctionJson',
    'explode' => '_baseConverterFunctionExplode',
    'trim' => '_baseConverterFunctionTrim',
    'parseRegexp' => '_baseConverterFunctionParseRegexp',
    'strCase' => '_baseConverterFunctionStrCase',
    'arrayElement' => '_baseConverterFunctionArrayElement',
    'implode' => '_baseConverterFunctionImplode',
    'curlGetContent' => '_baseConverterFunctionCurlGetContent',
    'parseUrlParams' => '_baseConverterFunctionParseUrlParams',
);

foreach ($importConverters as $converterName => $converterFn) {
    Converters::registerConverter($converterName, $converterFn);
}