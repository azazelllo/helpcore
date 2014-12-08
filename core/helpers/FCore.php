<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 30.11.14
 * Time: 6:56
 */

class FCore {
    private static $_abs = 'qwertyuiopasdfghjklzxcvbnm1234567890_';
    private static $_randomStrScope = array();
    public static function getRandomString($len=10){
        $res = '';
        for($i=0;$i<$len;$i++){
            $res .= self::$_abs[rand(0, strlen(self::$_abs)-1)];
        }
        if(in_array($res, self::$_randomStrScope)){
            $res = self::getRandomString($len);
        }else{
            self::$_randomStrScope[] = $res;
        }
        return $res;
    }
} 