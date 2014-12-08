<?php
/**
 * Created by PhpStorm.
 * User: azazello
 * Date: 29.11.14
 * Time: 22:15
 */

/**
 * Контейнер различных валидаторов
 *
 * Class Validators
 */
class Validator
{
    /**
     * @var array Функции валидатора
     */
    private static $validateFunctionsMap;

    /**
     * Проверяет правило $ruleName на значении $value
     *
     * @param $ruleName string имя правила
     * @param $value mixed проверяемое значение
     * @param $params mixed параметры для правила
     * @return array результат проверки. Имеет следующую структуру <br/>
     * <pre>
     * array(
     *   'isValid' => <bool>, true, если валидация пройдена, иначе false
     *   'info' => <mixed> информация о проверке которую вернуло правило. Устанавливается только, если валидация провалена
     * )
     * </pre>
     */
    private static function _validateRule($ruleName, $value, $params = null)
    {
        $result = array(
            'isValid' => false,
            'info' => null
        );
        if (isset(self::$validateFunctionsMap[$ruleName])) {
            $ruleFn = self::$validateFunctionsMap[$ruleName];
            try {
                $resultValid = $ruleFn($value, $params);
            } catch (Exception $e) {
                $resultValid = $e->getMessage();
            }
            if ($resultValid === true) {
                $result['isValid'] = true;
            } else {
                $result['isValid'] = false;
                $result['info'] = $resultValid;
            }
        } else {
            $result['info'] = "not register rule '$ruleName'";
        }
        return $result;
    }

    /**
     * Регистрирует правило с именем $ruleName и функцией валидации $ruleFn <br/>
     * Если уже имеется правило с таким именем, то оно будет заменено <br/>
     * $ruleFn - функция принимающая один или два параметра: <br/>
     * первый параметр - это валидируемое значение, <br/>
     * а второй параметр(опционально) - это произвольное значение
     * которое должно представлять кастомизационную информацию для валидатора <br/>
     * Функция должна вернуть явный <b>true</b> в случае успешно пройденной валидации,
     * иначе валидация будет считаться не пройденной, а возвращенное функцией значение
     * (или сообщение выбрашенного из нее исключения) будет расцениваться информация об ошибке валидирования<br/>
     * Функция может быть передана как строковое название существующей функции,
     * а так же как анонимная функция (PHP >= 5.3.0)
     *
     * @param $ruleName string имя правила
     * @param $ruleFn string|function
     * @throws Exception
     */
    public static function registerRule($ruleName, $ruleFn)
    {
        if (!is_callable($ruleFn)) {
            throw new Exception("ruleFn - '$ruleFn' is not a callable.");
        }
        self::$validateFunctionsMap[$ruleName] = $ruleFn;
    }

    /**
     * Удаляет правило из валидатора
     *
     * @param $ruleName string название правила
     */
    public static function removeRegisterRule($ruleName)
    {
        if (isset(self::$validateFunctionsMap[$ruleName])) {
            unset(self::$validateFunctionsMap[$ruleName]);
        }
    }

    /**
     * Выполняет "долгую" валидацию
     *
     * @param $value
     * @param $rules
     * @return array
     */
    private static function _validateLong($value, $rules)
    {
//        $rules = [
//            [
//                'name' => 'ruleName1',
//                'params' => 'ruleParams1'
//            ],
//            [
//                'name' //...
//            ]
//            //...
//        ];
        $result = array(
            'isValidAll' => true,
            'isValidOneOrMore' => false,
            'detail' => array()
        );

        foreach ($rules as $rule) {
            $ruleName = $rule['name'];
            $ruleParams = $rule['params'];
            $curRuleValidateResult = self::_validateRule($ruleName, $value, $ruleParams);
            $curRuleValidateResult['ruleName'] = $ruleName;
            $result['detail'][] = $curRuleValidateResult;
            $result['isValidAll'] = !!($result['isValidAll'] && $curRuleValidateResult['isValid']);
            $result['isValidOneOrMore'] = !!($result['isValidOneOrMore'] || $curRuleValidateResult['isValid']);
        }
        return $result;
    }

    /**
     * Выполняет быструю ("ленивую") валидацию
     *
     * @param $value
     * @param $rules
     * @return bool
     */
    private static function _validateFast($value, $rules)
    {
        $result = true;
        foreach ($rules as $rule) {
            $ruleName = $rule['name'];
            $ruleParams = $rule['params'];
            $curRuleValidateResult = self::_validateRule($ruleName, $value, $ruleParams);
            if (!$curRuleValidateResult['isValid']) {
                $result = false;
                break;
            }
        }
        return $result;
    }

    private static function  _parseValidateParamsAdapter($argumentsArr)
    {
        /**
         * 1) Быстрая валидация на одно правило(без возможности просмотра детальной информации о результатах валидации)
         * validate(<mixed>$value, '<string>ruleName', '<mixed>ruleParams1', '<mixed>ruleParams2', '<mixed>ruleParams3')
         * 2) Валидация на одно правило, с возможностью выбора детального просмотра
         * validate(<mixed>$value, [<string>ruleName, <mixed>ruleParams1, <mixed>ruleParams2, <mixed>ruleParams3], <bool>isDetail=false)
         * 3) Универсальная валидация на произвольное чилсло правил с возможностью выбора детального просмотра
         * validate(<mixed>$value, [
         *              [<string>ruleName, <mixed>ruleParams1, <mixed>ruleParams2, ...],
         *              [<string>ruleName, <mixed>ruleParams1, <mixed>ruleParams2, ...],
         *              [<string>ruleName]
         *          ], <bool>isDetail=false)
         */

        $value = $argumentsArr[0];
        $ruleParam = null;
        $rules = array();
        $isDetail = false;
        if (!is_array($argumentsArr[1])) {//1 вариант(второй аргумент является строкой)
            $ruleName = $argumentsArr[1];
            $ruleParam = $argumentsArr;
            unset($ruleParam[0]);
            unset($ruleParam[1]);
            $ruleParam = array_values($ruleParam);//Параметрами права является массив из остальных аргументов
            if (!count($ruleParam)) {//Если больше нет аргументов, то значит параметры правила равны null
                $ruleParam = null;
            } else if (count($ruleParam) == 1) {//Если аргумент остался всего один, то значит он и будет параметром права
                $ruleParam = $ruleParam[0];
            }
            $rules[] = array(
                'name' => $ruleName,
                'params' => $ruleParam,
            );
        } else if (!is_array($argumentsArr[1][0])) {//2 вариант(второй аргумент является массивом, первый аргумент которого - строка)
            $ruleName = $argumentsArr[1][0];
            $ruleParam = $argumentsArr[1];
            unset($ruleParam[0]);
            $ruleParam = array_values($ruleParam);//Остаток от массива считается параметром правила
            if (!count($ruleParam)) {//Если больше нет аргументов, то значит параметры правила равны null
                $ruleParam = null;
            } else if (count($ruleParam) == 1) {//Если аргумент остался всего один, то значит он и будет параметром права
                $ruleParam = $ruleParam[0];
            }
            $rules[] = array(
                'name' => $ruleName,
                'params' => $ruleParam,
            );
            if (isset($argumentsArr[2]) && !!$argumentsArr[2]) {
                $isDetail = true;
            }
        } else {// 3 вариант(второй аргумент является двумерным массивом)
            $ruleList = $argumentsArr[1];
            foreach ($ruleList as $rule) {
                $ruleName = $rule[0];
                $ruleParam = $rule;
                unset($ruleParam[0]);
                $ruleParam = array_values($ruleParam);
                if (!count($ruleParam)) {//Если больше нет аргументов, то значит параметры правила равны null
                    $ruleParam = null;
                } else if (count($ruleParam) == 1) {//Если аргумент остался всего один, то значит он и будет параметром права
                    $ruleParam = $ruleParam[0];
                }
                $rules[] = array(
                    'name' => $ruleName,
                    'params' => $ruleParam,
                );
            }
            if (isset($argumentsArr[2]) && !!$argumentsArr[2]) {
                $isDetail = true;
            }
        }
        $result = array(
            'value' => $value,
            'rules' => $rules,
            'isDetail' => $isDetail,
        );
        return $result;
    }


    /**
     * Осуществляет валидацию значения $value набором правил из $rules <br/>
     * Если параметр $isReturnDetail эквивалентен false, то валидация будет
     * проходить лишь до тех пор, пока очередное правило не сообщит об ошибке валидации(в
     * этом случае последующие правила не будут проверяться и результатом будет false),<br/>
     * Если значение $value проходит проверку на все правила из $rules, то будет возвращено true<br/>
     * <br/>
     * Если параметр $isReturnDetail эквивалентен true, то валидация будет проводиться по всем правилам
     * из $rules, а результаты проерок будут уложены в массив, который по окончании проверок вернет функция.<br/>
     * Формат этого массива следующий: <br/>
     * <pre>
     * array(
     *   'isValidAll' => <bool>, //true - если валидацию прошли все правила
     *   'isValidOneOrMore' => <bool>, //true - если валидацию прошло хотябы одно правило
     *   'detail' => array( //массив с результатами проверок по правилам
     *      array(
     *          'nameRule' => 'nameRule1', //Название правила из $rules
     *          'isValid' => <bool>, //true - если валидация на этом правиле пройдена
     *          'info' => <mixed>, // Информация от правила, если валидация не был пройдена
     *          'params' => <mixed>, //Параметры переданные валидатору
     *      ),
     *      ...
     *    )
     * )
     * </pre>
     *
     * @param $value
     * @param $rules array|string массив правил вида: <br/>
     * todo Сделать это дерьмо удобнее
     * <pre>
     * array(
     *    array('ruleName1', <ruleParams1>), //Можно задавать правило в виде двумерного массива. Первый элемент - это
     *                                       //название правила, а второй - параметры правила
     *    array('ruleName2', <ruleParams2>, <ruleTwoParams2>), // Если массив будет состоять более чем из двух элементов
     *                                                         // то все элементы начиная со второго будут собраны
     *                                                         // в один массив и передадуться правилу в качестве
     *                                                         // параметра
     *     'ruleName3', //Если параметры в правило передаать не нужно, то можно задать правило как строку содержащую
     *                 //название правила
     * )
     * </pre>
     * Если же требуется проверить только одно правило, то можно вместо массива передать его название, в таком случае,
     * значение параметра $isReturnDetail будет расцениваться как параметр для валидатора.
     * При таком способе вызова функции, нет возможности получить
     * детальную информацию о результатах валидации
     * @param bool $isReturnDetail
     * @return array|bool
     */
    public static function validate($value, $rules, $isReturnDetail = null)
    {
        $arguments = func_get_args();
        $parseArgResult = self::_parseValidateParamsAdapter($arguments);
        $result=null;
        if($parseArgResult['isDetail']){
            $result = self::_validateLong($parseArgResult['value'], $parseArgResult['rules']);
        }else{
            $result = self::_validateFast($parseArgResult['value'], $parseArgResult['rules']);
        }
        return $result;
    }

} 