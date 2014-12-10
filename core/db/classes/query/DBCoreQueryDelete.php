<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 05.12.14
 * Time: 3:04
 */

/**
 * Class DBCoreQueryDelete
 */
class DBCoreQueryDelete extends DBCoreQueryWhere{
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

    private function _build(){
        $sql = 'DELETE FROM ' . $this->tableName;
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