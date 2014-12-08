<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 30.11.14
 * Time: 5:43
 */

/**
 * Класс для построения запросов типа SELECT
 *
 * Class DBQuerySelect
 * @method DBCoreCoreQuerySelect or ()
 * @method DBCoreCoreQuerySelect and ()
 */
class DBCoreCoreQuerySelect extends DBCoreQueryWhere
{
    private $selectFields;
    private $fromTables;
    protected $where;
    private $order;
    private $limit;
    protected $bindArray;
    /**
     * @var DBCore
     */
    private $dbCore;


    public function __construct($fields=null, $dbCore=null)
    {
        $this->bindArray = array();
        parent::__construct();
        if(!is_null($fields)){
            $this->select($fields);
        }
        $this->dbCore = $dbCore;
    }

    /**
     * @param $fields
     * @return $this DBQuerySelect
     */
    public function select($fields = '*')
    {
        $this->selectFields = $fields;
        return $this;
    }


    public function from($tables)
    {
        $this->fromTables = $tables;
        return $this;
    }

    public function orderBy()
    {
        $arguments = func_get_args();
        $this->order = Converters::convert($arguments, array(
            'trim',
            array('implode', ', ')
        ));
        return $this;
    }

    public function limit($limit, $offset=0){
        $limitBindParamName = ':l_'.FCore::getRandomString();
        $offsetBindParamName = ':o_'.FCore::getRandomString();
        $this->limit = "$limitBindParamName OFFSET $offsetBindParamName";
        $this->bindArray[$limitBindParamName] = $limit;
        $this->bindArray[$offsetBindParamName] = $offset;
        return $this;
    }

    public function build()
    {
//        $res = $this->where;
        $sql = 'SELECT ' . $this->selectFields . ' FROM ' . $this->fromTables;
        if ($this->where) {
            $sql .= ' WHERE ' . $this->where;
        }
        if($this->order){
            $sql .= ' ORDER BY '.$this->order;
        }
        if($this->limit){
            $sql .= ' LIMIT '.$this->limit;
        }
        $bind = $this->bindArray;
        $res = array(
            'sql' => $sql,
            'bind' => $bind
        );
        return $res;
    }

    public function execute($resultType=null, $bindArray=array()){
        $bindArray = array_merge($bindArray, $this->bindArray);
        $buildSql = $this->build();
        $buildSql = $buildSql['sql'];
        $result =  $this->dbCore->query($buildSql, $resultType, $bindArray);
        return $result;
    }

}