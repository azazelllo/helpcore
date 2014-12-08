<?php

/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 29.11.14
 * Time: 19:57
 */

/**
 * Представляет собой итератор выборки из БД
 *
 * Class DBCoreRowListIterator
 */
class DBCoreRowListIterator implements Iterator
{

    /**
     * Подготовленный запрос
     *
     * @var PDOStatement
     */
    private $pdoStatement;

    /**
     * @var int Тип выборки(PDO::FETCH_*)
     */
    private $fetchStyle;

    /**
     * Текущая позиция курсора(текущий номер строки из выборки) начинается с 0
     * @var int
     */
    private $curPos;

    /**
     * @var mixed Текущая запись
     */
    private $curValue;

    /**
     * @param $pdoStatement PDOStatement подготовленный запрос, уже "сбинденный"
     * @param $fetchStyle int Тип выборки(PDO::FETCH_*)
     */
    public function __construct($pdoStatement, $fetchStyle)
    {
        $this->pdoStatement = $pdoStatement;
        $this->fetchStyle = $fetchStyle;
        $this->rewind();
    }

    private function _fetch(){
        $res = $this->pdoStatement->fetch($this->fetchStyle);
        return $res;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return $this->curValue;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->curPos++;
        $this->curValue = $this->_fetch();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->curPos;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return $this->curValue!==false;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->pdoStatement->closeCursor();
        $this->pdoStatement->execute();
        $this->curPos = 0;
        $this->curValue = $this->_fetch();
    }
}