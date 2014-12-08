<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 29.11.14
 * Time: 17:57
 */

/**
 * Низкоуровневое общение с БД
 * Каждый экземпляр представляет собой одно подключение к БД
 *
 * Позволяет абстрогироваться от тонкостей подключения к конкретной БД
 *
 * Class DBCore
 */
class DBCore{
    /**
     * Константы типов результата метода query
     */

    /**
     * Итератор ассоциативных массивов
     */
    const RESULT_TYPE_LIST_ITERATOR_ASSOC=0;

    /**
     * Итератор объектов
     */
    const RESULT_TYPE_LIST_ITERATOR_OBJECT = 1;

    /**
     * Первая запись из выборки в виде
     * ассоциативного массива
     */
    const RESULT_TYPE_ROW_ASSOC = 2;

    /**
     * Первая запись из выборки в виде
     * объекта
     */
    const RESULT_TYPE_ROW_OBJECT = 3;

    /**
     * Значение первого поля первой записи
     * выборки
     */
    const RESULT_TYPE_ONE = 4;

    /**
     * Вернет id последней вставленной
     * записи
     */
    const RESULT_TYPE_INS_ID = 5;

    /**
     * Ни чего не возвращает
     */
    const RESULT_TYPE_VOID = 6;

    /**
     * Вернет подготовленный запрос типа PDOStatement
     */
    const RESULT_TYPE_PDO_STATEMENT = 7;

    /*
     * Вернет список ассоциированных массивов из выборки
     * Не безопасно по памяти, если число записей большое - используйте с умом
     */
    const RESULT_TYPE_ALL_ASSOC = 8;

    /**
     * Вернет список массивов из выборки
     * Не безопасно по памяти, если число записей большое - используйте с умом
     */
    const RESULT_TYPE_ALL_OBJECT = 9;

    /**
     * Тип результата по умолчанию
     */
    const RESULT_TYPE_DEFAULT = self::RESULT_TYPE_LIST_ITERATOR_OBJECT;

    /**
     * Подключение к БД
     *
     * @var PDO
     */
    private $pdo;

    private $dbh;

    /**
     * @var DBCore
     */
    private static $defaultDBCore;

    /**
     * @var int Кол-во открытых транзакций
     */
    private $countTransaction;

    private static function _getParamType($var){
        $resultParamType = PDO::PARAM_STR;
        switch(gettype($var)){
            case 'boolean':
                $resultParamType = PDO::PARAM_BOOL;
                break;
            case 'integer':
                $resultParamType = PDO::PARAM_INT;
                break;
            case 'NULL':
                $resultParamType = PDO::PARAM_NULL;
                break;
        }
        return $resultParamType;
    }

    private function _resetSelect(){
        $this->currentSelect = array(
            'fields' => array(),
            'from' => array(),
            'where' => array(),
            'orderBy' => array(),
            'limit' => array()
        );
    }

    /**
     * @param $dbh string DSN, содержащее информацию, необходимую для подключения к базе данных.
     */
    public function __construct($dbh, $username=null, $password=null, $driverOptions=null){
        $this->dbh = $dbh;
        $this->pdo = new PDO($dbh, $username, $password, $driverOptions);
        $this->pdo->query('SET NAMES utf8');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->countTransaction = 0;
        $this->_resetSelect();
    }

    public function __destruct(){
        if($this->countTransaction>0){
            $this->rollBackTransaction();
        }
    }

    /**
     * Выполняет запрос к БД
     *
     * @param $sql string Пред-подготовленный запрос с именованными параметрами, которые должны передаваться в $bind
     * @param $resultType int Тип результата
     * @param $bind
     * @return DBCoreRowListIterator|mixed|null|PDOStatement|string
     */
    public function query($sql, $resultType= null, $bind=null, $seqName=null){
        if(is_null($resultType)){
            $resultType = self::RESULT_TYPE_DEFAULT;
        }
        $q = $this->pdo->prepare($sql);
        /*Если имеются данные для биндинга*/
        if(is_array($bind)){
            /*Результирующий(валидный) биндинг*/
            $prepareBind = array();
            foreach($bind as $variable => $value){
                /*Если значение не скалярное, то игнорируем его*/
                if(!is_scalar($value) && !is_null($value)){
                    continue;
                }
                /*Фикс - имя параметра должно начинаться с двоеточия*/
                if($variable[0]!=':'){
                    $variable = ':'.$variable;
                }
                /*Если такой параметр не имеется в sql-запросе, то игнорируем его*/
                if(strpos($sql, $variable)===false){
                    continue;
                }

                $paramType = self::_getParamType($value);
                $prepareBind[$variable] = array(
                    'value' => $value,
                    'type' => $paramType,
                );
            }
            /*Связываем переменные*/
            foreach ($prepareBind as $variable => &$valueInfo) {
                $q->bindParam($variable, $valueInfo['value'], $valueInfo['type']);
            }
        }
        $result = null;

        /*Если надо вернуть подготовленный запрос, но не выполнять его*/
        if($resultType==self::RESULT_TYPE_PDO_STATEMENT){
            $result = $q;
        }else{
            /*Ведем себя по разному в соответствии запрашиваемым типом результата*/
            switch($resultType){
                /*Если надо вернуть список ассоциативных массивов*/
                case self::RESULT_TYPE_LIST_ITERATOR_ASSOC:
                    $result = new DBCoreRowListIterator($q, PDO::FETCH_ASSOC);
                    break;
                case self::RESULT_TYPE_LIST_ITERATOR_OBJECT:
                    $result = new DBCoreRowListIterator($q, PDO::FETCH_OBJ);
                    break;
                case self::RESULT_TYPE_INS_ID:
                    //Если БД - pgsql то надо передавать имя секвенции(Подумать на досуге об этом костыле)
                    $q->execute();
                    if(preg_match('#^pgsql#', $this->dbh) && !is_null($seqName)){
                        $seq = $seqName;
                    }else{
                        $seq = null;
                    }
                    $result = $this->pdo->lastInsertId($seq);
                    break;
                case self::RESULT_TYPE_ONE:
                    $q->execute();
                    $result = $q->fetchColumn();
                    break;
                case self::RESULT_TYPE_ROW_ASSOC:
                    $q->execute();
                    $result = $q->fetch(PDO::FETCH_ASSOC);
                    break;
                case self::RESULT_TYPE_ROW_OBJECT:
                    $q->execute();
                    $result = $q->fetchObject();
                    break;
                case self::RESULT_TYPE_VOID:
                    $q->execute();
                    break;
                case self::RESULT_TYPE_ALL_ASSOC:
                    $q->execute();
                    $result = $q->fetchAll(PDO::FETCH_ASSOC);
                    break;
                case self::RESULT_TYPE_ALL_OBJECT:
                    $q->execute();
                    $result = $q->fetchAll(PDO::FETCH_OBJ);
                    break;
            }
        }
        return $result;
    }

    /**
     * Система "вложенных" транзакций.
     * Эмулируется система вложенных друг в друга транзакций.
     * Если открыто n "вложенных" транзакций, то результирующую транзакцию можно будет закоммитить
     * лишь при n коммитах
     */

    /**
     * Открывает очередную транзакцию
     * если уровер вложенности был 0 - то создается реальная транзакция
     */
    public function beginTransaction(){
        if($this->countTransaction==0){
            $this->pdo->beginTransaction();
        }
        $this->countTransaction++;
    }

    /**
     * Накатывает очередную транзакцию
     * Если уровень вложенности 1 - то происходит реальное накатывание
     * Если открытых транзакций нет, то метод ни чего не делает
     */
    public function commitTransaction(){
        if($this->countTransaction==1){
            $this->pdo->commit();
        }
        if($this->countTransaction>0){
            $this->countTransaction--;
        }
    }

    /**
     * Откатывает транзакцию, и сбрасывает уровень вложенности
     * Если к моменту вызова транзакций не было - то ни чего не делает
     */
    public function rollBackTransaction(){
        if($this->countTransaction>0){
            $this->pdo->rollBack();
        }
        $this->countTransaction=0;
    }

    /**
     * Вернет число вложенных транзакций
     *
     * @return int
     */
    public function getCountTransaction(){
        return $this->countTransaction;
    }

    /**
     * Вернет объект PDO связанный с базой, для низко-уровневого доступа
     *
     * @return PDO
     */
    public function getPDO(){
        return $this->pdo;
    }

    /**
     * Помощник в построении запросов выборки
     *
     * @param $fields string список полей для выборки в "сыром" виде
     * @return DBCoreCoreQuerySelect
     */
    public function select($fields){
        return new DBCoreCoreQuerySelect($fields, $this);
    }

    /**
     * @param $tableName
     * @return DBCoreQueryInsert
     */
    public function insert($tableName){
        return new DBCoreQueryInsert($tableName, $this);
    }

    /**
     * @param $tableName
     * @return DBCoreQueryUpdate
     */
    public function update($tableName){
        return new DBCoreQueryUpdate($tableName, $this);
    }

    public static function setDefaultDB($defaultDBCore){
        self::$defaultDBCore = $defaultDBCore;
    }

    /**
     * @return DBCore
     */
    public static function DB(){
        return self::$defaultDBCore;
    }
}