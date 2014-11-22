<?php

namespace Model;

use Zend\ServiceManager\ServiceManager;
use Model\Collection\AbstractCollection as Collection;
use Model\Cond\AbstractCond as Cond;
use Model\Entity\AbstractEntity as Entity;
use Model\Exception\ErrorException as ErrorException;
use Model\Result\Result;
use Model\Validator\ValidatorSet;
use Model\Filter\AbstractFilter;
use Zend\Validator\AbstractValidator;

/**
 * Class AbstractModel
 * @package Model
 */
class AbstractModel extends Singleton implements ModelInterface
{
    /**
     * Связь много ко многим
     */
    const MANY_TO_MANY = 'ManyToMany';

    /**
     * Связь один ко многим
     */
    const ONE_TO_MANY = 'OneToMany';

    /**
     * Связь много к одному
     */
    const MANY_TO_ONE = 'ManyToOne';

    /**
     * Связь один к одному
     */
    const ONE_TO_ONE = 'OneToOne';

    /**
     * Основной ключ
     */
    const INDEX_PRIMARY = 'PRIMARY';

    /**
     * Просто индекс
     */
    const INDEX_KEY = 'KEY';

    /**
     * Уникальный ключ
     */
    const INDEX_UNIQUE = 'UNIQUE';

    /**
     * @var string
     */
    private $entity;

    /**
     * @var string
     */
    private $collection;

    /**
     * @var string
     */
    private $cond;

    /**
     * Имя модели
     * @var string
     */
    private $_name;

    /**
     * @var string
     */
    private $rawName;

    /**
     * Значения по-умолчанию для полей Entity
     *
     * @see https://github.com/esteit/model/wiki/%D0%94%D0%BE%D0%B1%D0%B0%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D0%B5-%D0%B4%D0%B0%D0%BD%D0%BD%D1%8B%D1%85
     * @var array
     */
    private $defaultsRules = array();

    /**
     * Каскад значений для фильтра при добавлении
     * @see https://github.com/esteit/model/wiki/%D0%94%D0%BE%D0%B1%D0%B0%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D0%B5-%D0%B4%D0%B0%D0%BD%D0%BD%D1%8B%D1%85
     * @var array
     */
    private $filterCascadeRulesOnAdd = array();

    /**
     * Каскад значений для фильтра при обновлении
     * @see https://github.com/esteit/model/wiki/%D0%94%D0%BE%D0%B1%D0%B0%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D0%B5-%D0%B4%D0%B0%D0%BD%D0%BD%D1%8B%D1%85
     * @var array
     */
    private $filterCascadeRulesOnUpdate = array();

    /**
     * Правила фильтрации
     *
     * @var array
     */
    private $filterRules;

    /**
     * @var array
     */
    private $relation;

    /**
     * Правила валидации при добавлении
     *
     * @var array
     */
    private $validatorRulesOnAdd;

    /**
     * Правила валидации при обновлении
     *
     * @var array
     */
    private $validatorRulesOnUpdate;

    /**
     * @var ValidatorSet|null
     */
    private $validatorOnAdd;

    /**
     * @var ValidatorSet|null
     */
    private $validatorOnUpdate;

    private static $serviceManager;

    /**
     * @return ServiceManager
     */
    public static function getServiceManager()
    {
        return self::$serviceManager;
    }

    /**
     * @param ServiceManager $serviceManager
     */
    public static function setServiceManager(ServiceManager $serviceManager)
    {
        self::$serviceManager = $serviceManager;
    }

    public function __construct()
    {
        $this->init();

        if (empty($this->_name)) {
            throw new ErrorException('Model name is not defined');
        }

        if (empty($this->entity)) {
            $this->entity = $this->_name . 'Entity';
        }

        if (empty($this->collection)) {
            $this->collection = $this->_name . 'Collection';
        }

        if (empty($this->cond)) {
            $this->cond = $this->_name . 'Cond';
        }
    }

    public function __call($method, $params)
    {
        $view = null;
        if (count($segments = explode('By', $method, 2)) == 2) {
            list($basePart, $by) = $segments;

            if (count($_segments = explode('As', end($segments), 2)) == 2) {
                if (strpos('And', end($_segments)) === null) {
                    list($by, $view) = $method;
                }
            }
        } else {
            $basePart = $method;
            $by = '';

            if (count($__segments = explode('As', $basePart, 2)) == 2) {
                list($by, $view) = $__segments;
            }
        }

        $isGet  = false;
        if (substr($basePart, 0, 3) == 'get') {
            $type = 'get';
            $shift = 3;
            $isGet  = true;
        } elseif (substr($basePart, 0, 6) == 'exists') {
            $type = 'exists';
            $shift = 6;
        } else {
            throw new ErrorException('Unknown __call type: ' . $method);
        }

        $basePartCount = strlen($basePart) - $shift;

        $alias = null;
        if ($isGet && $basePartCount >= 10 && substr($basePart, -$basePartCount) == 'Collection') {
            $condType = Cond::FETCH_ALL;
            $alias = substr($basePart, $shift, -$basePartCount);
        } elseif ($isGet && $basePartCount >= 5 && substr($basePart, -$basePartCount) == 'Count') {
            $condType = Cond::FETCH_COUNT;
            $alias = substr($basePart, $shift, -$basePartCount);
        } elseif ($basePartCount > 3) {
            $alias = substr($basePart, $shift);
        }

        $byParams      = explode('And', $by);
        $byParamsCount = count($byParams);
        $cond          = $this->prepareCond(isset($params[$byParamsCount]) ? $params[$byParamsCount] : null, $alias);

        if (isset($condType)) {
            $cond->type($condType);
        }

        if ($type == 'exists') {
            $cond->columns(array('id'))
                ->type(Cond::FETCH_ONE);
        }

        $params[$byParamsCount] = $cond;

        $callMethod = 'get';
        if ($by) {
            $callMethod = 'getBy' . $by;
        }

        if ($view) {
            $callMethod .= 'As' . $view;
        }

        return call_user_func_array(array($this, $callMethod), $params);
    }


    /**
     * Инициализация модели
     */
    public function init()
    {
    }

    /**
     * @param $relation
     *
     * @return $this
     */
    public function setRelation($relation)
    {
        $this->relation = (array)$relation;
        return $this;
    }

    /**
     * @return array
     */
    public function getRelation()
    {
        $this->initRelation();
        return (array)$this->relation;
    }

    /**
     * @return bool
     */
    protected function isRelation()
    {
        $this->initRelation();
        return !empty($this->relation);
    }

    /**
     *
     */
    public function initRelation()
    {
        if (!is_array($this->relation)) {
            $this->setupRelation();
        }
    }

    /**
     *
     */
    protected function setupRelation()
    {
        $this->relation = array();
    }
    /**
     * @param        $itemArray
     * @param Cond   $cond
     * @return mixed
    protected function beforePrepare($itemArray, Cond $cond = null)
    {
        return $itemArray;
    }
     */

    /**
     * @param                   $itemArray
     * @param Cond              $cond
     * @return mixed
    public function afterPrepare($itemArray, Cond $cond = null)
    {
        return $itemArray;
    }
     */

    /**
     * @param      $itemArray
     * @param Cond $cond
     * @return mixed
     * @throws ErrorException
     */
    public function prepare($itemArray, Cond $cond = null)
    {
        $cond = $this->prepareCond($cond);

        /**
         * If prepare disabled return raw input
         */
        $returnType = $cond->getCond(Cond::PREPARE_ENTITY, Cond::PREPARE_DEFAULT);
        if ($returnType == Cond::PREPARE_DISABLE) {
            return $itemArray;
        }

        /**
         * Before prepare hook
         */
        if (method_exists($this, 'beforePrepare') && is_array($itemArray)) {
            $itemArray = $this->beforePrepare($itemArray, $cond);
        }

        if (empty($itemArray)) {
            // return empty value in right type
            return $cond->getEmptySelectResult();
        }

        $withParams = $cond->getWithParams();

        if (!empty($withParams)) {
            $relationArray = $this->getRelation();

            foreach ($withParams as $withEntity => $withParam) {
                $strippedWithEntity = preg_replace('#(Collection|_collection|Count|_count)$#s', '', $withEntity);
                if (!isset($relationArray[$strippedWithEntity])) {
                    continue;
                    //throw new ErrorException('Unknown relation "' . $strippedWithEntity . '"');
                }

                $relation = $relationArray[$strippedWithEntity];

                if (!isset($itemArray[$relation['local_column']])) {
                    $_entity = $cond->getEntityClassName();
                    $itemArray['_' . $withEntity] = new $_entity;
                    continue;
                }

                $entityLastPart = explode('_', $withEntity);
                $entityLastPart = end($entityLastPart);

                switch ($entityLastPart) {
                    case 'collection':
                        $type = 'Collection';
                        break;
                    case 'count':
                        $type = 'Count';
                        break;
                    default:
                        $type = '';
                        break;
                }

                $foreignEntityAliasAsCamelCase = implode('', array_map('ucfirst', explode('_', $relation['foreign_entity'])));
                $localEntity                   = implode('', array_map('ucfirst', explode('_', $relation['local_entity'])));

                /** @var $foreignModel \Model\AbstractModel */
                $foreignModel = $relation['foreign_model'];

                switch ($relation['type']) {
                    /** When relation one to one
                     * we have next variations:
                     *
                     * table1.id == table2.id
                     * table1.alias_id == table2.id
                     * table1.id == table2.alias_id
                     * table1.alias1_id == table2.alias2_id (WTF?) @dirty Need to test this logic
                     */
                    case AbstractModel::ONE_TO_ONE:
                        //$fetchId = $itemArray[$relation['local_column']];
                        $fetchId = $itemArray['id'];

                        $method = 'get';

                        // If foreign is aliased entity
                        if ($relation['foreign_entity'] != $relation['foreign_table']) {
                            $method .= $foreignEntityAliasAsCamelCase;
                        }

                        $localEntityAliasAsCamelCase = implode('', array_map('ucfirst', explode('_', $relation['local_entity'])));

                        $method .= 'By' . $localEntityAliasAsCamelCase;
                        break;
                    /**
                     * Variations:
                     *
                     * table1.id == table2.id (WTF???) @dirty One of this ids must be non autoincrement (its huge problem)
                     * table1.alias_id == table2.id
                     * table1.id == table2.alias_id
                     */
                    case AbstractModel::MANY_TO_ONE:
                        $fetchId = $itemArray[$relation['local_column']];

                        $method = 'get';

                        // If foreign is aliased entity
                        if ($relation['foreign_entity'] != $relation['foreign_table']) {
                            $method .= $foreignEntityAliasAsCamelCase;
                        }

                        if ($relation['foreign_column'] != 'id') {
                            print_r($relation);
                        }
                        $method .= 'ById';
                        break;
                    case AbstractModel::ONE_TO_MANY:
                            $fetchId = $itemArray[$relation['local_column']];
                            $localEntityAliasAsCamelCase = implode('', array_map('ucfirst', explode('_', $relation['local_entity_alias'])));
                            $method = 'get' . $type . 'By' . $localEntityAliasAsCamelCase;
                        break;
                    case AbstractModel::MANY_TO_MANY:
                        $fetchId = $itemArray[$relation['local_column']];

                        // If foreign is aliased entity
                        if ($relation['foreign_entity'] != $relation['foreign_table']) {
                            $method = 'get' . $type . $foreignEntityAliasAsCamelCase . 'By' . $localEntity;
                        } else {
                            $localEntityAliasAsCamelCase = implode('', array_map('ucfirst', explode('_', $relation['local_entity_alias'])));
                            $method = 'get' . $type . 'By' . $localEntityAliasAsCamelCase;
                        }

                        $itemArray['_' . $withEntity] = $foreignModel::getInstance()->$method($fetchId, $cond->getWith($withEntity));
                        break;
                    default:
                        throw new ErrorException('Unknown relation type: ' . $relation['type']);
                }

                $itemArray['_' . $withEntity] = $foreignModel::getInstance()->$method($fetchId, $cond->getWith($withEntity));
            }
        }

        /**
         * Before prepare hook
         */
        if (method_exists($this, 'afterPrepare') && is_array($itemArray)) {
            $itemArray = $this->afterPrepare($itemArray, $cond);
        }

        switch ($returnType) {
            // return as entity
            case Cond::PREPARE_DEFAULT:
                $entityClass = $cond->getEntityClassName();
                return new $entityClass($itemArray);
                break;

            // return as an array
            case Cond::PREPARE_ARRAY:
                return (array)$itemArray;
                break;

            // If return type is not class throw an exception
            default:
                if (!class_exists($returnType)) {
                    throw new ErrorException("Class {$returnType} not found");
                }
                return new $returnType($itemArray);
        }
    }

    /**
     * @param           $collectionArray
     * @param null|Cond $cond
     * @param null      $pager
     * @return mixed
     */
    public function prepareCollection($collectionArray, $cond = null, $pager = null)
    {
        foreach ($collectionArray as &$itemArray) {
            $itemArray = $this->prepare($itemArray, $cond);
        }
        /** @var Cond $cond */
        $collectionName = $cond->getCollectionClassName();

        /** @var Collection $collection */
        $collection = new $collectionName($collectionArray);
        $collection->setPager($pager);
        return $collection;
    }

    /**
     * Установить имя модели
     * @param string $name
     */
    public function setName($name)
    {
        $this->rawName = trim($name);
        $this->_name = implode(array_map('ucfirst', explode('_', (string)$name)));
    }

    /**
     * Получить имя модели
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get raw model name
     *
     * @return string
     */
    public function getRawName()
    {
        return $this->rawName;
    }

    /**
     * Получить объект условий
     *
     * @param null $entityName
     * @param null $type
     * @return Cond
     */
    public function getCond($entityName = null, $type = null)
    {
        if (!$entityName) {
            $entityName = $this->getRawName();
        }

        return self::condFactory($entityName, $type);
    }

    /**
     * @param null $name
     * @param null $type
     * @return Cond
     */
    public static function condFactory($name = null, $type = null)
    {
        if ($type) {
            $class = implode('', array_map('ucfirst', explode('_', $type)));

            if (substr($class, -4) != 'Cond') {
                $class .= 'Cond';
            }

            if (substr($class, 0, 12) != '\\Model\\Cond\\') {
                $class = '\\Model\\Cond\\' . $class;
            }
        } else {
            $strippedEntityName = preg_replace('#(Collection|_collection|Count|_count)$#s', '', $name);
            $camelCase = implode('', array_map('ucfirst', explode('_', $strippedEntityName)));

            /** @var $class Cond */
            $class = '\\Model\\Cond\\' . (string)$camelCase . 'Cond';
        }

        if (!class_exists($class)) {
            $class = '\\Model\\Cond\\Cond';
        }

        $result = $class::init($name, $type);

        return $result;
    }

    /**
     * @param Cond        $cond
     * @param string|null $entity
     * @param string|null $type
     * @return Cond
     */
    public function prepareCond(Cond $cond = null, $entity = null, $type = null)
    {
        return !$cond ? $this->getCond($entity, $type) : (clone $cond);
    }

    /**
     * Фильтрация данных при добавлении в базу данных
     *
     * @param                   $data
     * @param Cond              $cond
     * @return array
     */
    public function filterOnAdd($data, Cond $cond = null)
    {
        return $this->filterData('add', (array)$data, $cond);
    }

    /**
     * Фильтрация данных при изменении полей в базе данных
     *
     * @param                   $data
     * @param Cond              $cond
     * @return array
     */
    public function filterOnUpdate($data, Cond $cond = null)
    {
        return $this->filterData('update', (array)$data, $cond);
    }

    /**
     * Фильтрация данных
     *
     * @param                   $type
     * @param array             $data
     * @param Cond              $cond
     * @return array
     */
    private function filterData($type, array $data, Cond $cond = null)
    {
        $isAdd = ($type == 'add');
        $condFilterValues = $isAdd ? Cond::FILTER_ON_ADD : Cond::FILTER_ON_UPDATE;
        $condFilterRemoveUnknownValues = $isAdd ? Cond::FILTER_ON_ADD_REMOVE_UNKNOWN_KEYS : Cond::FILTER_ON_UPDATE_REMOVE_UNKNOWN_KEYS;

        // Подготавляиваем объект условий
        $cond = $this->prepareCond($cond);

        if (empty($data)) {
            return $data;
        }

        // Получаем правила фильтрации
        $filterRules = $this->getFilterRules();

        if ($cond->checkCond($condFilterValues, true) && !empty($filterRules)) {
            foreach ($filterRules as $field => $filterRulesArray) {
                if (isset($data[$field])) {
                    foreach ($filterRulesArray as $filterRule) {
                        $data[$field] = $filterRule($data[$field]);
                    }
                }
            }
        }

        if ($cond->getCond($condFilterRemoveUnknownValues, true) == true) {
            foreach (array_keys($data) as $k) {
                if (!isset($filterRules[$k])) {
                    unset($data[$k]);
                }
            }
        }

        return $data;
    }

    /**
     * Отфильтровать значение поля
     *
     * @param mixed $value
     * @param mixed $field
     * @return mixed
     */
    public function filterValue($value, $field)
    {
        if (!isset($this->filterRules)) {
            $this->getFilterRules();
        }

        if (is_array($value)) {
            foreach ($value as &$v) {
                $v = $this->filterValue($v, $field);
            }
        } else {
            $value = (string)$value;

            if (!empty($value) && isset($this->filterRules[$field]) && is_array($this->filterRules[$field])) {
                foreach ($this->filterRules[$field] as $filter) {
                    $value = $filter($value);
                    if (empty($value)) {
                        break;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Проверить данные на добавлении
     *
     * @param array $data
     * @return ValidatorSet
     */
    public function validateOnAdd(array $data)
    {
        $validatorSet = $this->getValidatorSet(true);
        $validatorSet->setData($data);
        return $validatorSet;
    }

    /**
     * Проверить данные при обновлении полей в базе данных
     *
     * @param array             $data
     * @return ValidatorSet
     */
    public function validateOnUpdate(array $data)
    {
        $validatorSet = $this->getValidatorSet(false);
        $validatorSet->setData($data);

        return $validatorSet;
    }

    /**
     * Получить объект для валидации данных
     *
     * @param bool $required
     * @return ValidatorSet
     */
    private function getValidatorSet($required = false)
    {
        $validatorSet = (bool)$required ? $this->validatorOnAdd : $this->validatorOnUpdate;

        if (!$validatorSet) {
            $validatorRules = $this->getValidatorRules($required);
            $validatorSet = ValidatorSet::create($validatorRules);

            if ($required) {
                $this->validatorOnAdd = $validatorSet;
            } else {
                $this->validatorOnUpdate = $validatorSet;
            }
        }

        return $validatorSet;
    }

    /**
     * @return array
     */
    protected function getValidatorOnAdd()
    {
        return $this->validatorOnAdd;
    }

    /**
     * @return array
     */
    protected function getValidatorOnUpdate()
    {
        return $this->validatorOnUpdate;
    }

    /**
     * Получить правила валидации
     *
     * @param bool $required указывает, что нужно проверять отсутствующие поля или нет
     * @return array
     */
    protected function getValidatorRules($required = false)
    {
        if ($required && !empty($this->validatorRulesOnAdd)) {
            return $this->validatorRulesOnAdd;
        } elseif (!$required && !empty($this->validatorRulesOnUpdate)) {
            return $this->validatorRulesOnUpdate;
        }

        $this->validatorRulesOnAdd = array();
        $this->validatorRulesOnUpdate = array();
        $this->initValidatorRules($required);

        return $required ? $this->validatorRulesOnAdd : $this->validatorRulesOnUpdate;
    }

    /**
     * Инициализация правил валидации
     *
     * @param bool $required
     * @return void
     */
    protected function initValidatorRules($required = false)
    {
        $this->setupValidatorRules($required);
    }

    /**
     * @param bool $required
     */
    protected function setupValidatorRules($required = false)
    { }

    /**
     *
     * @param      $field
     * @param      $validator
     * @param bool $required Обязательно должно быть при добавлении
     *
     * @return $this
     */
    protected function addValidatorRule($field, $validator, $required = true)
    {
        $field = (string)$field;

        $this->validatorRulesOnAdd[$field]['name']         = $field;
        $this->validatorRulesOnAdd[$field]['validators'][] = $validator;
        $this->validatorRulesOnAdd[$field]['required']     = $required;

        $this->validatorRulesOnUpdate[$field]['name']         = $field;
        $this->validatorRulesOnUpdate[$field]['validators'][] = $validator;
        $this->validatorRulesOnUpdate[$field]['required']     = false;

        return $this;
    }


    /**
     * Проверить значение поля на правильность
     *
     * @param $value
     * @param $field
     * @return bool|AbstractValidator
     */
    public function validateValue($value, $field)
    {
        $value = $this->filterValue($value, $field);
        $validatorRules = $this->getValidatorRules(false);

        if (isset($validatorRules[$field]['validators'])) {
            /** @var $validatorRules AbstractFilter[] */
            $validatorRules = $validatorRules[$field]['validators'];

            foreach ($validatorRules as $validator) {
                if (!$validator->isValid($value)) {
                    return $validator;
                }
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function isFilterRules()
    {
        return !empty($this->filterRules);
    }

    /**
     * Получить правила фильтрации
     *
     * @return array
     */
    protected function getFilterRules()
    {
        if ($this->filterRules) {
            return $this->filterRules;
        }

        $this->filterRules = array();
        $this->initFilterRules();

        return $this->filterRules;
    }

    /**
     * Добавить правило фильтрации для поля
     *
     * @param $field
     * @param AbstractFilter $filter
     *
     * @return $this
     */
    protected function addFilterRule($field, AbstractFilter $filter)
    {
        $this->filterRules[(string) $field][] = $filter;
        return $this;
    }

    /**
     * Инициализация правил фильтрации
     */
    protected function initFilterRules()
    {
        $this->setupFilterRules();
    }

    /**
     * Настройка правил фильтрации пользователем
     */
    protected function setupFilterRules()
    { }


    /**
     * Получить правила каскада при добавлении
     *
     * @return array
     */
    protected  function getFilterCascadeRulesOnAdd()
    {
        if (!$this->filterCascadeRulesOnAdd) {
            $this->setupFilterCascadeRules();
        }

        return $this->filterCascadeRulesOnAdd;
    }

    /**
     * Получить правила каскада при добавлении
     *
     * @return array
     */
    protected  function getFilterCascadeRulesOnUpdate()
    {
        if (!$this->filterCascadeRulesOnUpdate) {
            $this->setupFilterCascadeRules();
        }

        return $this->filterCascadeRulesOnUpdate;
    }

    /**
     *
     */
    protected function setupFilterCascadeRulesOnAdd()
    {}

    /**
     *
     */
    protected function setupFilterCascadeRules()
    {}

    /**
     *
     *
     * @param $name
     * @param $parentName
     * @param $allowedOnUpdate
     *
     * @return $this
     */
    protected function addFilterCascadeParent($name, $parentName, $allowedOnUpdate)
    {
        if (!is_array($parentName)) {
            $parentName = array($parentName);
        }

        foreach ($parentName as $pName) {
            $this->filterCascadeRulesOnAdd[$name][] = $pName;
            if ($allowedOnUpdate) {
                $this->filterCascadeRulesOnUpdate[$name][] = $pName;
            }
        }
        return $this;
    }

    /**
     * Получить значения полей по-умолчению
     *
     * @return array
     */
    protected function getDefaultsRules()
    {
        if (!$this->defaultsRules) {
            $this->initDefaultsRules();
        }

        return $this->defaultsRules;
    }

    /**
     * @return bool
     */
    protected function isDefaultRules()
    {
        return (bool)$this->defaultsRules;
    }

    /**
     *
     * @param $field
     * @param $value
     * @return $this
     */
    public function setDefaultRule($field, $value)
    {
        $this->defaultsRules[(string)$field] = (string) $value;
        return $this;
    }

    /**
     * Инициализация значений по-умолчанию
     */
    protected function initDefaultsRules()
    {
        $this->setupDefaultsRules();
    }

    /**
     * Настройка значений по-умолчанию пользователем
     */
    protected function setupDefaultsRules()
    {
    }

    /**
     * Выбираем нужные поля из $inputData
     *
     * @param array $inputData   Входные данные
     * @param array $defaultData Нужные поля
     * @param array $result      Результат
     * @param bool  $replace     Если в result данные присутствуют то их заменяем или нет
     * @return array
     */
    public function applyDefaultValues($inputData, array $defaultData = null, array &$result = array(), $replace = false)
    {
        if (!$defaultData) {
            $defaultData = $this->getDefaultsRules();
        }

        if (!$defaultData) {
            return $inputData;
        }

        $result = array_merge($result, $inputData);

        $field = null;
        $default = null;
        foreach ($defaultData as $k => &$v) {
            if (is_int($k)) {
                $field = $v;
                unset($default);
            } else {
                $field = $k;
                $default = $v;
            }

            if (isset($default) && (!isset($result[$field]) || $replace)) {
                $result[$field] = $default;
            }
        }

        return $result;
    }

    /**
     * Получить отсутствующие значения из других полей на основе каскада
     *
     * Каскад прописывается следующим образом:
     * array(
     *     'name' => array('title', 'param'),
     * )
     *
     * Это означает, что если поле name пустое, оно
     * берется из title, если и title пустой то из param
     *
     * @param array $inputData     входные данные
     * @param array $cascadeValues массив каскада
     * @return array
     */
    protected static function applyFilterCascadeRules($inputData, $cascadeValues)
    {
        if (!is_array($inputData) || !is_array($cascadeValues) || empty($inputData) || empty($cascadeValues)) {
            return $inputData;
        }

        foreach ($cascadeValues as $field => $analogList) {
            // если поля во входных данных есть, а запись о каскаде присутствует
            // то пытаемся найти замену
            if (!isset($inputData[$field]) || empty($inputData[$field])) {
                foreach ((array)$analogList as $analog) {
                    if (isset($inputData[$analog])) {
                        $inputData[$field] = $inputData[$analog];
                        continue;
                    }
                }
            }
        }

        return $inputData;
    }

    /**
     * Return array of ids from mixed data
     *
     * @param mixed        $data
     * @param string|mixed $callbackPrepare
     * @throws ErrorException
     * @return array of ids
     */
    public static function getIdsFromMixed($data, $callbackPrepare = 'intval')
    {
        $ids = array();

        if (is_null($data)) {
            $ids = array();
        } elseif (is_scalar($data) && $callbackPrepare == 'intval') { // speed up
            return array((int)$data);
        } elseif ($data instanceof Entity) {
            $ids[] = $data->getId();
        } elseif ($data instanceof Collection) {
            $ids = $data->getIdsAsArray();
        } /* elseif ($data instanceof Result) {
            $ids = array_map($callbackPrepare, $data->toArray());
        } */ elseif (is_array($data)) {
            if (reset($data) instanceof Entity) {
                /** @var array|Entity[] $data */
                foreach ($data as $entity) {
                    $ids[] = $entity->getId();
                }
            } else {
                $keys = array_keys($data);
                if (is_int(reset($keys)) && !is_array(reset($data))) { // нам нужен именно array, а не struct
                    $ids = array_map($callbackPrepare, $data);
                }
            }
        } elseif ($data instanceof Result) {
            return self::getIdsFromMixed($data->getResult(), $callbackPrepare);
        } elseif (is_string($data) && strpos($data, ',') !== false) {
            $ids = array_map($callbackPrepare, explode(',', $data));
        } elseif (is_string($data) && strpos($data, '|') !== false) {
            $ids = array_map($callbackPrepare, explode('|', $data));
        } elseif (is_scalar($data)) {
            $ids = array_map($callbackPrepare, array($data));
        } elseif ($data) {
            throw new ErrorException("Entity must be instance of Model_Entity_Interface or Model_Collection_Interface");
        }

        // Вынесено из filterEmpty сюда ради оптимизации
        foreach ($ids as $k => &$v) {
            if (empty($v) && !is_null($v)) {
                unset($ids[$k]);
            }
        }

        return $ids;
    }

    /**
     * @param $str
     * @return string
     */
    protected function _underscoreToCamelCaseFilter($str)
    {
        return implode('', array_map('ucfirst', explode('_', $str)));
    }
}