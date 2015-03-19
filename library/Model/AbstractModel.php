<?php

namespace Model;

use Model\Collection\AbstractCollection as Collection;
use Model\Cond\AbstractCond as Cond;
use Model\Entity\AbstractEntity as Entity;
use Model\Exception\ErrorException;
use Model\Filter\AbstractFilter;
use Model\Stdlib\ArrayUtils;
use Model\Validator\ValidatorSet;

/**
 * Class AbstractModel
 * @package Model
 */
abstract class AbstractModel extends Singleton
{
    /**
     * Link many to many
     */
    const MANY_TO_MANY = 'ManyToMany';

    /**
     * СLink one to many
     */
    const ONE_TO_MANY = 'OneToMany';

    /**
     * Link many to one
     */
    const MANY_TO_ONE = 'ManyToOne';

    /**
     * Link one to one
     */
    const ONE_TO_ONE = 'OneToOne';

    /**
     * Primary key
     */
    const INDEX_PRIMARY = 'PRIMARY';

    /**
     * Index
     */
    const INDEX_KEY = 'KEY';

    /**
     * Unique key
     */
    const INDEX_UNIQUE = 'UNIQUE';

    /**
     * Entity name
     *
     * @var string
     */
    private $entity;

    /**
     * Collection name
     *
     * @var string
     */
    private $collection;

    /**
     * Condition name
     *
     * @var string
     */
    private $cond;

    /**
     * Model name
     *
     * @var string
     */
    private $_name;

    /**
     * DB table name
     *
     * @var string
     */
    private $rawName;

    /**
     * Filter rules
     *
     * @var array
     */
    protected $filterRules;

    /**
     * Validation rules
     *
     * @var array
     */
    protected $validatorList;

    /**
     * Required validation fields. Used when adding data
     *
     * @var array
     */
    protected $validatorRequiredFields = array();

    /**
     * Relation with other model
     *
     * @var array
     */
    protected $relation;

    /**
     * Default values for fields
     *
     * @var array
     */
    protected $defaultsRules = null;

    /**
     * Cascade filter rules on add
     *
     * @var array
     */
    protected $filterCascadeRulesOnAdd = array();

    /**
     * Cascade filter rules on update
     *
     * @var array
     */
    protected $filterCascadeRulesOnUpdate = array();

    /**
     * Constructor model
     *
     * @throws ErrorException
     */
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

    /**
     * Universal call method
     *
     * @param $method
     * @param $params
     * @return mixed
     * @throws ErrorException
     */
    public function __call($method, $params)
    {
        if (count($segments = explode('By', $method, 2)) == 2) {
            list($basePart, $by) = $segments;
        } else {
            $basePart = $method;
            $by = '';
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
            throw new ErrorException('Unknown __call type');
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

        return call_user_func_array(array($this, $callMethod), $params);
    }


    /**
     * Model initialize
     */
    public function init()
    { }

    /**
     * Initialize relations
     */
    protected function initRelation()
    {
        $this->relation = array();
        $this->setupRelation();
    }

    /**
     * User defined relations
     */
    protected function setupRelation()
    { }

    /**
     * Get relations
     *
     * @return array
     */
    public function getRelation()
    {
        if (is_null($this->relation)) {
            $this->initRelation();
        }
        return $this->relation;
    }

    /**
     * Set relations
     *
     * @return array
     */
    protected function setRelation(array $relation)
    {
        $this->relation = $relation;
        return $this;
    }

    /**
     * Before prepare hook. Override this method in some models, if necessary
     *
     * @param        $itemArray
     * @param Cond   $cond
     * @return mixed
     */
    public function beforePrepare($itemArray, Cond $cond = null)
    {
        return $itemArray;
    }

    /**
     * After prepare hook. Override this method in some models, if necessary
     *
     * @param                   $itemArray
     * @param Cond              $cond
     * @return mixed
     */
    public function afterPrepare($itemArray, Cond $cond = null)
    {
        return $itemArray;
    }

    /**
     * Prepare data
     *
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
        $itemArray = $this->beforePrepare($itemArray, $cond);

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
                    throw new ErrorException('Unknown relation "' . $strippedWithEntity . '"');
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
        $itemArray = $this->afterPrepare($itemArray, $cond);

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
     * Prepare collections
     *
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
     * Set model name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->rawName = trim($name);
        $this->_name = implode(array_map('ucfirst', explode('_', (string)$name)));
    }

    /**
     * Get model name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get raw model name (table name)
     *
     * @return string
     */
    public function getRawName()
    {
        return $this->rawName;
    }

    /**
     * Get Cond object
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
        if (!$cond) {
            return $this->getCond($entity, $type);
        } else {
            return clone $cond;
        }
    }

    /**
     * Execute query
     */
    public function execute()
    { }

    /**
     * Change underscore string to CamelCase
     *
     * @param $str
     * @return string
     */
    protected function _underscoreToCamelCaseFilter($str)
    {
        return implode('', array_map('ucfirst', explode('_', $str)));
    }

    /**
     * Add validator rule for field
     *
     * @param $field
     * @param $validator
     * @param $required
     */
    public function addValidatorRule($field, $validator, $required)
    {
        $this->validatorList[$field][] = $validator;

        if ((bool)$required && !in_array($field, $this->validatorRequiredFields)) {
            $this->validatorRequiredFields[] = $field;
        }
    }

    /**
     * Validate input data for add. Validation with required values
     *
     * @param array $data
     * @param Cond $cond
     * @return ValidatorSet
     */
    public function validateOnAdd(array $data, Cond $cond = null)
    {
        $validator = $this->getValidator($data, true);

        return $validator->validate();
    }

    /**
     * Validate input data for update
     *
     * @param array $data
     * @param Cond $cond
     * @return ValidatorSet
     */
    public function validateOnUpdate(array $data, Cond $cond = null)
    {
        $validator = $this->getValidator($data, false);

        return $validator->validate();
    }

    /**
     * Get ValidatorSet object for add or update
     *
     * @param array $data
     * @param bool $withRequiredFields
     *
     * @return ValidatorSet
     */
    private function getValidator(array $data, $withRequiredFields)
    {
        // important! getValidatorList must be initialized BEFORE getting required fields
        $validatorList = $this->getValidatorList();
        $requiredFields = $withRequiredFields ? $this->validatorRequiredFields : array();
        
        return new ValidatorSet($validatorList, $data, $requiredFields);
    }

    /**
     * Filter input data on add
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
     * Filter input data on update
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
     * Filter input data
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

        $cond = $this->prepareCond($cond);

        if (empty($data)) {
            return $data;
        }

        $filterRules = $this->getFilterRules();

        if ($cond->checkCond($condFilterValues, true) && !empty($filterRules)) {
            foreach ($filterRules as $field => $filterRulesArray) {
                if (isset($data[$field])) {
                    /** @var AbstractFilter $filterRule */
                    foreach ($filterRulesArray as $filterRule) {
                        $data[$field] = $filterRule->filter($data[$field]);
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
     * Filter some value for field
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
                    /** @var AbstractFilter $filter */
                    $value = $filter->filter($value);
                    if (empty($value)) {
                        break;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Get empty validator list
     *
     * @return array
     */
    public function getValidatorList()
    {
        if (isset($this->validatorList)) {
            return $this->validatorList;
        }

        $this->validatorList = array();
        $this->initValidatorRules();

        return $this->validatorList;
    }

    /**
     * Init validation rules
     *
     * @return void
     */
    public function initValidatorRules()
    {
        $this->setupValidatorRules();
    }

    /**
     * Setup validation rules hook. User defined rules override in this method
     */
    public function setupValidatorRules()
    { }

    /**
     * Set default rule for some field
     *
     * @param $field
     * @param $value
     * @return $this
     */
    public function setDefaultRule($field, $value)
    {
        $this->defaultsRules[$field] = $value;
        return $this;
    }

    /**
     * Check initialize default rules
     *
     * @return bool
     */
    public function isDefaultRules()
    {
        return isset($this->defaultsRules);
    }

    /**
     * Get filter rules
     *
     * @return array
     */
    public function getFilterRules()
    {
        if ($this->isFilterRules()) {
            return $this->filterRules;
        }

        $this->filterRules = array();
        $this->initFilterRules();

        return $this->filterRules;
    }

    /**
     * Add filter rule for some field
     *
     * @param $field
     * @param $filter
     * @return $this
     */
    public function addFilterRule($field, $filter)
    {
        $this->filterRules[$field][] = $filter;
        return $this;
    }

    /**
     * Check initialize filter rules
     *
     * @return bool
     */
    public function isFilterRules()
    {
        return isset($this->filterRules);
    }

    /**
     * Initialize filter rules
     */
    public function initFilterRules()
    {
        $this->setupFilterRules();
    }

    /**
     * Setup filter rules hook. User defined rules override in this method
     */
    public function setupFilterRules()
    { }

    /**
     * Get default rules
     *
     * @return array
     */
    public function getDefaultsRules()
    {
        if (!$this->isDefaultRules()) {
            $this->initDefaultsRules();
        }

        return $this->defaultsRules;
    }

    /**
     * Initialize default rules
     */
    protected function initDefaultsRules()
    {
        $this->defaultsRules = array();
    }

    /**
     * Setup default rules hook. User defined rules override in this method
     */
    protected function setupDefaultsRules()
    { }

    /**
     * Apply default values for some data set
     *
     * @param array $inputData   Входные данные
     * @param array $defaultData Нужные поля
     * @return array
     */
    public function applyDefaultValues($inputData, array $defaultData = null)
    {
        if (!$defaultData) {
            $defaultData = $this->getDefaultsRules();
        }

        if (!$defaultData) {
            return $inputData;
        }

        $result = ArrayUtils::merge($defaultData, $inputData);

        return $result;
    }

    /**
     * Get cascade filter rules on add
     *
     * @return array
     */
    public function getFilterCascadeRulesOnAdd()
    {
        if (!$this->filterCascadeRulesOnAdd) {
            $this->setupFilterCascadeRules();
        }

        return $this->filterCascadeRulesOnAdd;
    }

    /**
     * Get cascade filter rules on update
     *
     * @return array
     */
    public function getFilterCascadeRulesOnUpdate()
    {
        if (!$this->filterCascadeRulesOnUpdate) {
            $this->setupFilterCascadeRules();
        }

        return $this->filterCascadeRulesOnUpdate;
    }

    /**
     *
     * Cascade :
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
    public static function applyFilterCascadeRules($inputData, $cascadeValues)
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
     * Настройка каскада пользователем
     *
     * Каскад прописывается следующим образом:
     * array(
     *     'name' => array('title', 'param'),
     * )
     *
     * Это означает, что если поле name пустое, оно
     * берется из title, если и title пустой то из param
     *
     * Записывать нужно в переменные $this->addFilterCascadeRules
     * и $this->updateFilterCascadeRules
     */
    public function setupFilterCascadeRules()
    { }

    /**
     * @param $data
     * @return array
     */
    protected function prepareData($data)
    {
        if (is_array($data)) {
            return $data;
        } elseif ($data instanceof Entity) {
            $data = $data->toArray();
        } elseif ($data instanceof Collection) {
            $data = $data->current()->toArray();
        } elseif (is_object($data) && method_exists($data, 'toArray')) {
            $data = $data->toArray();
        }

        return (array)$data;
    }

    protected function beforePrepareOnAdd(array $data, Cond $cond = null)
    {
        return $data;
    }

    protected function beforePrepareOnAddOrUpdate(array $data, Cond $cond = null)
    {
        return $data;
    }

    protected function afterPrepareOnAdd(array $data, Cond $cond = null)
    { }

    protected function afterPrepareOnAddOrUpdate(array $data, Cond $cond = null)
    { }

    /**
     * Prepare data before add
     *
     * @param      $data
     * @param Cond $cond
     *
     * @throws \Model\Exception\ErrorException
     * @return array
     */
    protected function prepareDataOnAdd($data, Cond $cond = null)
    {
        $data = $this->prepareData($data);
        $cond = $this->prepareCond($cond);

        if (method_exists($this, 'beforePrepareOnAdd')) {
            $data = $this->beforePrepareOnAdd($data, $cond);
        }

        if (method_exists($this, 'beforePrepareOnAddOrUpdate')) {
            $data = $this->beforePrepareOnAddOrUpdate($data, $cond);
        }

        // Применяем умолчания
        if ($cond->checkCond(Cond::APPLY_DEFAULT_VALUES, true)) {
            $data = $this->applyDefaultValues($data);
        }

        // Если каскад разрешен, то применяем его
        if ($cond->checkCond(Cond::FILTER_CASCADE_ON_ADD, true)) {
            $data = $this->applyFilterCascadeRules($data, $this->getFilterCascadeRulesOnAdd());
        }

        // Фильтруем входные данные
        if ($cond->checkCond(Cond::FILTER_ON_ADD, true)) {
            $data = $this->filterOnAdd($data);
        }

        if (method_exists($this, 'afterPrepareOnAdd')) {
            // Вносить изменения в данные нельзя
            $this->afterPrepareOnAdd($data, $cond);
        }

        if (method_exists($this, 'afterPrepareOnAddOrUpdate')) {
            // Вносить изменения в данные нельзя
            $this->afterPrepareOnAddOrUpdate($data, $cond);
        }

        return $data;
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
        } elseif (is_array($data)) {
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
        } elseif (is_string($data) && strpos($data, ',') !== false) {
            $ids = array_map($callbackPrepare, explode(',', $data));
        } elseif (is_string($data) && strpos($data, '|') !== false) {
            $ids = array_map($callbackPrepare, explode('|', $data));
        } elseif (is_scalar($data)) {
            $ids = array_map($callbackPrepare, array($data));
        } elseif ($data) {
            throw new ErrorException("Entity must be instance of Model_Entity_Interface or Model_Collection_Interface");
        }

        foreach ($ids as $k => &$v) {
            if (empty($v) && !is_null($v)) {
                unset($ids[$k]);
            }
        }

        return $ids;
    }

    /**
     * @param $data
     * @return int
     * @throws ErrorException
     */
    public function getFirstIdFromMixed($data)
    {
        $ids = $this->getIdsFromMixed($data);
        return !empty($ids) ? reset($ids) : null;
    }
}