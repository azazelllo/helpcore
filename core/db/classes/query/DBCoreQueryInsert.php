<?php

/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 05.12.14
 * Time: 1:30
 */
class DBCoreQueryInsert
{
    private $bind;
    private $tableName;
    private $insertFields;
    private $buildSQL;
    /**
     * @var DBCore
     */
    private $dbCore;

    public function __construct($tableName, $dbCore=null)
    {
        $this->dbCore = $dbCore;
        $this->tableName = $tableName;
        "INSERT INTO images(id, type, row_id, url)";
    }

    private function _values($insertDataArr)
    {
        $this->bind = array();
        $this->insertFields = array();

        foreach ($insertDataArr[0] as $fieldName => $fieldValue) {
            $this->insertFields[] = Converters::convert($fieldName, 'trim');
        }

        foreach ($insertDataArr as $numberRow => $insertRow) {
            foreach ($this->insertFields as $fieldName) {
                $fieldValue = $insertRow[$fieldName];
                $bindKey = ':iv_' . $numberRow . '_' . $fieldName;
                $this->bind[$bindKey] = $fieldValue;
            }
        }
    }

    private function _build()
    {
        $sql = 'INSERT INTO ' . $this->tableName;
        $sql .=
            '(' .
            Converters::convert($this->insertFields, 'implode', ', ')
            . ')';
        $sql .= ' VALUES ';
        $countRows = count($this->bind) / count($this->insertFields);
        $insData = array();

        for ($numberRow = 0; $numberRow < $countRows; $numberRow++) {
            $curInsData = array();
            foreach ($this->insertFields as $fieldName) {
                $curInsData[] = ':iv_' . $numberRow . '_' . $fieldName;
            }
            $insData[] =
                '(' .
                Converters::convert($curInsData, 'implode', ', ')
                . ')';
        }
        $sql .= Converters::convert($insData, 'implode', ',');
        $this->buildSQL = $sql;
    }

    /**
     * Проверяет массив данных на валидность <br/>
     *
     * Массив является валидным, только если он имеет один из двух видов: <br/>
     * <pre>
     * [
     *      'field1' => value1,
     *      'field2' => value2,
     *      ....
     * ]
     * или
     * [
     *      [
     *          'field1' => value1_1,
     *          'field2' => value1_2,
     *          ...
     *      ],
     *      [
     *          'field1' => value2_1,
     *          'field2' => value2_2,
     *          ...
     *      ],
     *      ...
     * ]
     * </pre>
     *
     *
     * @param $insertData
     * @return array
     * @throws Exception
     */
    private static function _valuesParamsAdapter($insertData)
    {
        $result = array();
        if (!is_array($insertData)) {
            throw new Exception('insert data is not array');
        }
        $isAllArray = true;
        $isAllNotArray = true;
        foreach ($insertData as $insertItem) {
            $isAllArray = $isAllArray && is_array($insertItem);
            $isAllNotArray = $isAllNotArray && !is_array($insertItem);
            if (!$isAllArray && !$isAllNotArray) {
                throw new Exception('invalid data structure');
            }
        }
        if (!$isAllArray) {
            $result[] = $insertData;
        } else {
            $fields = array();
            foreach ($insertData[0] as $fieldName => $value) {
                $fields[] = $fieldName;
            }
            $countFields = count($fields);
            foreach ($fields as $fieldName) {
                foreach ($insertData as $insertDataRow) {
                    if (!isset($insertDataRow[$fieldName]) || $countFields != count($insertDataRow)) {
                        throw new Exception('invalid data structure');
                    }
                }
            }
            $result = $insertData;
        }

        return $result;
    }

    public function values($insertData)
    {
        $this->_values($this->_valuesParamsAdapter($insertData));
        return $this;
    }

    public function execute(){
        $this->_build();
        $sql = $this->buildSQL;
        $bind = $this->bind;
        $seqName = $this->tableName . '_id_seq';
        $result = $this->dbCore->query($sql, DBCore::RESULT_TYPE_INS_ID, $bind, $seqName);
        return $result;
    }

    public function getSQLData()
    {
        $this->_build();
        $result = array(
            'sql' => $this->buildSQL,
            'bind' => $this->bind,
        );
        return $result;
    }
} 