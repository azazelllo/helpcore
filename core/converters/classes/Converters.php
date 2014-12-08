<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 30.11.14
 * Time: 1:25
 */

/**
 * Класс управляющий конвертацией
 *
 * Class Filters
 */
class Converters {

    /**
     * @var array список зарегистрированных конвертеров
     */
    private static $converterMap;

    private static function _convert($converterName, $converterValue, $params=null){
        $result = null;
        if(isset(self::$converterMap[$converterName])){
            $converterFn = self::$converterMap[$converterName];
            try{
                $result = $converterFn($converterValue, $params);
            }catch (Exception $e){
                $result = null;
            }
        }
        return $result;
    }

    private static function _parseArguments($arguments){

    }

    /**
     * Регистрирует конвертер с именем $converterName и функцией конвертации $converterFn <br/>
     * Если уже имеется конвертер с таким именем, то он будет заменен <br/>
     * $converterFn - функция принимающая один или два параметра: <br/>
     * первый параметр - это конвертируемое значение, <br/>
     * а второй параметр(опционально) - это произвольное значение
     * которое должно представлять кастомизационную информацию для конвертера <br/>
     * Функция должна вернуть результат конвертации, если она ни чего не вернет,
     * или выбросит исключение, то результатом конвертации будет считаться null<br/>
     * Функция может быть передана как строковое название существующей функции,
     * а так же как анонимная функция (PHP >= 5.3.0)
     *
     * @param $converterName string
     * @param $converterFn string|function
     * @throws Exception
     */
    public static function registerConverter($converterName, $converterFn){
        if(!is_callable($converterFn)){
            throw new Exception("converterFn - '$converterFn' is not a callable.");
        }
        self::$converterMap[$converterName] = $converterFn;
    }

    /**
     * Удаляет конвертор с именем $converterName
     *
     * @param $converterName string
     */
    public static function removeRegisterConverter($converterName){
        if(isset(self::$converterMap[$converterName])){
            unset(self::$converterMap[$converterName]);
        }
    }

    /**
     * Осуществляет конвертацию значения $value конвертером с именем $converterName <br/>
     * Функции - конвертеру вторым аргументом будут переданы параметры $params
     *
     * @param $value mixed
     * @param $converterName string
     * @param $params mixed
     * @return mixed
     */
    public static function convertOne($value, $converterName, $params=null){
        return self::_convert($converterName, $value, $params);
    }


    /**
     * Осуществляет конвеерную конвертацию значения $value конвертерами из списка $converters <br/>
     * Структура списка конвертеров следующая: <br/>
     * <pre>
     * array(
     *      array('converterName1', converterParams1), //Можно задавать конвертер как двумерный массив. Первым его
     *                                                 //элементом выступает название конвертера, а втормы параметры
     *      array('converterName2', converterParams2, converterParams3), //Если массив будет состоять более чем из двух
     *                                                                   //элементов то все элементы начиная со второго
     *                                                                   //будут собраны в один массив и передадуться
     *                                                                   //конвертеру в качестве параметра
     *      ...
     *      'convertName3' //Если передавать параметры не нужно, то можно задать конвертер как строку, содержащую
     *                     //название конвертера
     * )
     * </pre>
     *
     *
     * @param $value mixed
     * @param $converters array
     * @return mixed
     */
    public static function convertPool($value, $converters){
        $curValue = $value;
        foreach($converters as $converter){
            if(is_string($converter)){
                $converter = array($converter);
            }
            if(!isset($converter[0])){
                continue;
            }
            $converterName = $converter[0];
            unset($converter[0]);
            $converter = array_values($converter);
            if(!count($converter)){
                $converter = null;
            }else if(count($converter)==1){
                $converter = $converter[0];
            }
            $converterParams = $converter;
            $curValue = self::_convert($converterName, $curValue, $converterParams);
        }
        return $curValue;
    }


    /**
     * Удобная обертка для функций convertOne и convertPool <br/>
     * Если второй параметр - это массив, то результатом будет convertPool($value, $converters)<br/>
     * Иначе результатом будет convertOne($value, $converters, $params)
     *
     * @param $value
     * @param $converters
     * @param null $params
     * @return mixed|null
     */
    public static function convert($value, $converters, $params=null){
        $result = null;
        if(is_array($converters)){
            $result = self::convertPool($value, $converters);
        }else{
            $params = func_get_args();
            unset($params[0]);
            unset($params[1]);
            $params = array_values($params);
            if(!count($params)){
                $params = null;
            }else if(count($params)==1){
                $params = $params[0];
            }
            $result = self::convertOne($value, $converters, $params);
        }
        return $result;
    }


} 