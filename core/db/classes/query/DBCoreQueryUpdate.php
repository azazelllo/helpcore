<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 05.12.14
 * Time: 3:04
 */

/**
 * Class DBCoreQueryUpdate
 * @method DBCoreQueryUpdate set
 * @method DBCoreQueryUpdate and
 * @method DBCoreQueryUpdate or
 */
class DBCoreQueryUpdate extends DBCoreQueryWhere{
    private $tableName;
    private $sqlBind;
    private $bindMap;
    /**
     * @var DBCore
     */
    private $dbCore;

    public function __construct($tableName, $dbCore=null){
        $this->dbCore = $dbCore;
        $this->tableName = $tableName;
        parent::__construct();
    }

    public function __call($name, $arguments){
        if($name=='set'){
            return $this->_set($arguments);
        }
        return parent::__call($name, $arguments);
    }

    private static function _parseArguments($arguments){
        $result = array();

        if(is_array($arguments[0])){
            $result = $arguments[0];
        }else if(is_string($arguments[0]) && count($arguments)>1){
            $result[$arguments[0]] = $arguments[1];
        }else{
            throw new Exception('invalid arguments');
        }
        return $result;
    }

    private  function _set($setData){
        $setData = self::_parseArguments($setData);
        $this->bindArray = array();
        $this->bindMap = array();
        foreach($setData as $fieldName => $fieldValue){
            $bindName = ':up_' . $fieldName . FCore::getRandomString();
            $this->bindArray[$bindName] = $fieldValue;
            $this->bindMap[$fieldName] = $bindName;
        }
        return $this;
    }

    private function _build(){
        $sql = 'UPDATE ' . $this->tableName . ' SET ';
        $setArr = array();
        foreach($this->bindMap as $fieldName => $bindName){
            $setArr[] = $fieldName . '='  . $bindName;
        }
        $setArrStr = Converters::convert($setArr, 'implode', ', ');
        $sql .= $setArrStr;
        $sql .= ' WHERE ' . $this->where;
        $this->sqlBind = $sql;
    }

    public function execute($bindArray = array()){
        $this->_build();
        $sql = $this->sqlBind;
        $bind = array_merge($bindArray, $this->bindArray);
        $result = $this->dbCore->query($sql, DBCore::RESULT_TYPE_VOID, $bind);
        return $result;
    }

    public function getSQLData()
    {
        $this->_build();
        $result = array(
            'sql' => $this->sqlBind,
            'bind' => $this->bindArray,
        );
        return $result;
    }


} 