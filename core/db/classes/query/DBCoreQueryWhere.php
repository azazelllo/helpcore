<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 30.11.14
 * Time: 16:28
 */

/**
 * Class DBQueryWhere
 * @method DBCoreQueryWhere or()
 * @method DBCoreQueryWhere and()
 */
class DBCoreQueryWhere {
    protected $where;
    protected $bindArray;

    public function __construct(){
        $this->where = '';
//        $this->bindArray = array();
    }

    protected function _parseWhereSectionElement($params){
        $result = $params;
        if(is_array($params)){
            $property = $params[0];
            $operator = count($params)>2?$params[1]:'=';
            $value = count($params)>2?$params[2]:$params[1];
            $prepareVariableName = ':v_'.FCore::getRandomString();
            $this->bindArray[$prepareVariableName] = $value;
            $result = $property.' '.$operator.' '.$prepareVariableName;
        }
        return $result;
    }

    protected function _parseWhereSection($params){
        $lastElement = Converters::convert($params, array(
            array('arrayElement', 'last'),
            array('strCase', 'upper'),
        ));
        $concatType = 'AND';
        if($lastElement==='OR'){
            $concatType = 'OR';
            unset($params[count($params)-1]);
        }
        $result = array();
        foreach($params as $param){
            $result[] = '('.self::_parseWhereSectionElement($param).')';
        }
        $result = Converters::convert($result, 'implode', " $concatType ");
        return $result;
    }

    protected static function _prepareWhereArgs($args){
        $res = $args;
        if(is_string($args[0])){
            $res = array($args);
        }
        return $res;
    }

    public function where($q='1=1'){
        $args = self::_prepareWhereArgs(func_get_args());
        $this->where = '('.$this->_parseWhereSection($args).')';
        return $this;
    }

    protected function _and($args){
        $this->where .= " AND " . '('.$this->_parseWhereSection($args).')';
        return $this;
    }

    protected function _or($args){
        $this->where .= " OR " . '('.$this->_parseWhereSection($args).')';
        return $this;
    }

    public function __call($name, $arguments){
        if($name=='or'){
            $arguments = self::_prepareWhereArgs($arguments);
            return $this->_or($arguments);
        }else if($name=='and'){
            $arguments = self::_prepareWhereArgs($arguments);
            return $this->_and($arguments);
        }else{
            throw new Exception();
        }
    }
} 