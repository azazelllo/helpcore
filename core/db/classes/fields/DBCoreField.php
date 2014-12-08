<?php

/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 01.12.14
 * Time: 10:55
 */
class DBCoreField
{

    protected $config;

    public function __construct($config = array())
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    public function convertBeforeInsert($value)
    {
        return $value;
    }

    public function convertAfterSelect($value)
    {
        return $value;
    }

    public function validate($value)
    {
        return true;
    }

    protected function getDefaultConfig(){
        return array();
    }
} 