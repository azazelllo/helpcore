<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 05.12.14
 * Time: 14:21
 */

abstract class TaskCore {
    protected $inData;

    public function __construct($inData=null){
        $this->inData = $inData;
    }

    public abstract function run();

}