<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 05.12.14
 * Time: 14:25
 */

class TaskCoreProcessing extends TaskCore{
    private static $tasksMap;
    private $tasksQueue;

    public static function registerTask($name, $className){
        if(!class_exists($className)){
            throw new Exception("class $className is not exists");
        }
        $item = new $className();
        if(!($item instanceof TaskCore)){
            throw new Exception("class $className not instanceof TaskCore class");
        }
        unset($item);
        self::$tasksMap[$name] = $className;
    }

    public function tasks(){
        $arguments = func_get_args();
        $this->tasksQueue = array();
        foreach($arguments as $name){
            if(!isset(self::$tasksMap[$name])){
                throw new Exception("task $name not register");
            }
            $this->tasksQueue[] = $name;
        }
        return $this;
    }

    public function run()
    {
        $inData = $this->inData;
        foreach($this->tasksQueue as $taskName){
            try{
                $taskClass = self::$tasksMap[$taskName];
                /**
                 * @var TaskCore
                 */
                $task = new $taskClass($inData);
                $inData = $task->run();
            }catch (Exception $e){
                throw new TaskCoreException($taskName, $e->getMessage());
            }
        }
        return $inData;
    }
}