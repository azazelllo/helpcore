<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 30.11.14
 * Time: 1:17
 */

/**
 * Работа с таблицей
 *
 * Class DBTable
 */
class DBTable {
    /**
     * @var array Схема таблицы.
     */
    private $schema;

    private static function _schemaParse($schemaSrc){
        $schemaResult = $schemaSrc;
        return $schemaResult;
        
        $schemaExample = array(
            'tableName' => 'images',
            'idRowName' => 'id',
            'fields' => array(
                'type' => array(
                    'type' => 'string',
                    'convertBeforeInsert' => array(
                        'cutStr' => 255,
                        'toLowerCase' => true,
                    ),
                    'validate' => array(
                        'string' => true,
                        'strLen' => '<=255',
                        'tableExists' => true
                    )
                ),
                'row_id' => array(
                    'type' => 'int',
                    'convertBeforeInsert' => array(
                        'int' => true
                    ),
                    'validate' => array(
                        'int' => true,
                        'tableRowExist' => array(
                            'table' => '%{{CURRENT_ROW_FIELD_VALUE(type)}}%'
                        )
                    ),
                    'convertAfterSelect' => array(
                        'int' => true
                    )
                ),
                'url' => array(
                    'type' => 'string',
                )
            ),
        );
    }

    public function __construct($schema){
        $this->schema = $this::_schemaParse($schema);
    }

} 