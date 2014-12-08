<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 05.12.14
 * Time: 14:40
 */

class TaskCoreException extends Exception{
    private $taskName;
    private $taskError;

    public function __construct($taskName, $taskError){
        $this->taskName = $taskName;
        $this->taskError = $taskError;
        if(!is_scalar($taskError)){
            $taskError = serialize($taskError);
        }

        $message = "Task $taskName runtime error: $taskError";
        parent::__construct($message);
    }

    public function getInfo(){
        $result = array(
            'name' => $this->taskName,
            'error' => $this->taskError
        );
    }

    public function getName(){
        return $this->taskName;
    }

    public function getError(){
        return $this->taskError;
    }

} 