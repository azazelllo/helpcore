<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 29.11.14
 * Time: 21:28
 */

$curDir = realpath(dirname(__FILE__));

//DB

$files = array(
    '/db/classes/DBCore.php',
    '/db/classes/DBCoreRowListIterator.php',
    '/db/classes/query/DBCoreQueryWhere.php',
    '/db/classes/query/DBCoreQuerySelect.php',
    '/db/classes/query/DBCoreQueryInsert.php',
    '/db/classes/query/DBCoreQueryUpdate.php',
    '/db/classes/fields/DBCoreField.php',
    '/db/classes/fields/DBCoreFieldInteger.php',
    '/validators/classes/Validator.php',
    '/converters/classes/Converters.php',
    '/converters/basic.php',
    '/validators/basic.php',
    '/helpers/FCore.php',
    '/processing/TaskCore.php',
    '/processing/TaskCoreProcessing.php',
    '/processing/TaskCoreException.php',
);

foreach($files as $file){
    require $curDir . $file;
}