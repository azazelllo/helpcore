<?php

/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 01.12.14
 * Time: 11:25
 */
class DBCoreFieldInteger extends DBCoreField
{
    public function convertAfterSelect($value){
        $result = Converters::convert($value, 'integer');
        return $result;
    }

    public function validate($value)
    {
        $isUnsigned = !!$this->config['isUnsigned'];
        $result = Validator::validate($value, 'integer', $isUnsigned?'unsigned':null);
        return $result;
    }

    protected function getDefaultConfig(){
        return array(
            'isUnsigned' => false
        );
    }

} 