<?php
/**
 * Abstract Condition
 *
 * LICENSE: THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NON INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @category   Cond
 * @package    Model
 * @author     Eugene Myazin <eugene.myazin@gmail.com>
 * @copyright  2008-20013 Eugene Myazin <eugene.myazin@gmail.com>
 * @license    https://github.com/meniam/model/blob/master/MIT-LICENSE.txt  MIT License
 */
namespace Model\Cond;

use Model\AbstractModel;
use Model\Cond\Exception\ErrorException as ErrorException;
use Model\Cond\Join as JoinCond;
use Model\Db\Select;

/**
 * Объект условий
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
abstract class AbstractCond
{
    /**
     * Выбирать все, то есть массив записей
     * @var string
     */
    const FETCH_ALL   = 'ALL';

    /**
     * Выбирать парами
     * @var string
     */
    const FETCH_PAIRS = 'PAIRS';

    /**
     * Просто запустить, не возвращать результат
     * @var string
     */
    const FETCH_NONE  = 'EXECUTE';

    /**
     * Выбрать строку
     * @var string
     */
    const FETCH_ROW   = 'ROW';

    /**
     * Выбрать одно значение
     * @var string
     */
    const FETCH_ONE   = 'ONE';

    /**
     * Выбрать количество
     * @var string
     */
    const FETCH_COUNT = 'COUNT';

    /**
     * Ascending sort order
     */
    const ORDER_ASC              = 'ASC';

    /**
     * Descending sort order
     */
    const ORDER_DESC             = 'DESC';

    /**
     * Позиция вставки JOIN
     */
    const INSERT_JOIN_BEFORE     = 'before';

    /**
     * Позиция вставки JOIN
     */
    const INSERT_JOIN_AFTER      = 'after';

    /**
     * Позиция вставки JOIN
     */
    const INSERT_JOIN_INSTEAD    = 'instead';

    /**
     * Позиция вставки JOIN
     */
    const INSERT_JOIN_END        = 'end';

    /**
     * Flag indicates what we need an extended query view
     */
    const SHOW_QUERY_EXTENDED    = 'show_query_extended';

    /**
     * Flag indicates to show query after run
     */
    const SHOW_QUERY             = 'show_query';

    /**
     * Indicates what when we have new data in import we can update existed data
     */
    const UPDATE_ALLOWED         = 'update_allowed';

    /**
     * Says what import shall process children data in import
     */
    const CASCADE_ALLOWED         = 'cascade_allowed';

    /**
     * Ignore error on import
     */
    const IGNORE_ERRORS = 'ignore_errors';

    /**
     * Does import must append or renew links?
     */
    const APPEND_LINK = 'append_link';

    /**
     * Does add method must apply default values?
     */
    const APPLY_DEFAULT_VALUES   = 'apply_default_values';

    /**
     * Apply data cascade on add
     */
    const FILTER_CASCADE_ON_ADD  = 'filter_cascade_add_values';

    /**
     * Filter data on add
     */
    const FILTER_ON_ADD          = 'filter_add_values';

    /**
     * При добавлении убирать из массива ключи для которых нет фильтров
     */
    const FILTER_ON_ADD_REMOVE_UNKNOWN_KEYS = 'filter_update_remove_unknown_keys';

    /**
     * Проверять данные при добавлении
     */
    const VALIDATE_ON_ADD        = 'validate_add_values';

    /**
     * Применить каскад данных при изменении
     */
    const FILTER_CASCADE_ON_UPDATE      = 'filter_cascade_update_values';

    /**
     * Фильтровать данные при изменении
     */
    const FILTER_ON_UPDATE      = 'filter_update_values';

    /**
     * При изменении убирать из массива ключи для которых нет фильтров
     */
    const FILTER_ON_UPDATE_REMOVE_UNKNOWN_KEYS = 'filter_update_remove_unknown_keys';

    /**
     * Проверять данные при изменении
     */
    const VALIDATE_ON_UPDATE    = 'validate_update_values';

    /**
     * Enable hook after add item into database
     */
    const HOOK_AFTER_ADD = 'hook_after_add';

    /**
     * Fetch type
     *
     * @var string
     */
    const TYPE = 'type';

    /**
     * Условие выборки
     *
     * @var string
     */
    const WHERE = 'where';

    /**
     * Он и в Африке distinct
     *
     * @var string
     */
    const DISTINCT = 'distinct';

    /**
     * Having
     *
     * @var string
     */
    const HAVING = 'having';

    /**
     * Из какой таблицы выбираем
     *
     * @var string
     */
    const FROM   = 'from';

    /**
     * Ограничение количества на выборку
     *
     * @var string
     */
    const LIMIT   = 'limit';

    /**
     * Смещение
     *
     * @var string
     */
    const OFFSET   = 'offset';

    /**
     * Страница
     */
    const PAGE   = 'page';

    /**
     * Количество элементов на странице
     */
    const ITEMS_PER_PAGE   = 'items_per_page';

    /**
     * Колонки для выбора
     *
     * @var string
     */
    const COLUMNS = 'columns';

    /**
    * Сортировка
    *
    * @var string
    */
    const ORDER = 'order';

    /**
    * Группировка
    *
    * @var string
    */
    const GROUP = 'group';

    /**
     * Не делать обработку данных
     *
     * @var string
     */
    const WITHOUT_PREPARE = 'without_prepare';

    /**
     * Тип по-умолчанию
     */
    const PREPARE_DEFAULT   = 'default';

    /**
     * Ничего не делать
     */
    const PREPARE_DISABLE   = 'disable';

    /**
     * Массив
     */
    const PREPARE_ARRAY     = 'array';

    /**
     * Тип возвращаемый подготовкой Entity / prepare{Entity}
     */
    const PREPARE_ENTITY     = 'prepare_entity';

    /**
     * Тип возвращаемый подготовкой Collection / prepare{Entity}List
     */
    const PREPARE_COLLECTION = 'prepare_collection';

    /**
     * JOIN
     *
     * @var string
     */
    const JOIN = 'join';

    /**
     * INNER JOIN
     *
     * @var string
     */
    const JOIN_INNER = 'join_inner';

    /**
    * LEFT JOIN
    *
    * @var string
    */
    const JOIN_LEFT = 'join_left';

    /**
    * RIGHT JOIN
    *
    * @var string
    */
    const JOIN_RIGHT = 'join_right';

    /**
    * CROSS JOIN
    *
    * @var string
    */
    const JOIN_CROSS = 'join_cross';

    /**
    * FULL JOIN
    *
    * @var string
    */
    const JOIN_FULL = 'join_full';

    /**
    * NATURAL JOIN
    *
    * @var string
    */
    const JOIN_NATURAL = 'join_natural';

    /**
     * Есть ли хоть какой-то джоин любой сущности
     */
    const JOIN_CHECK_ANY = 'join_check_any';

    /**
     * Есть ли хоть какой-то джоин указанной сущности
     */
    const JOIN_CHECK_ANY_FOR_ENTITY = 'join_check_any_for_entity';

    /**
     * Заменить колонки
     */
    const COLUMNS_REPLACE = 'replace';

    /**
     * Добавить к уже существующим
     */
    const COLUMNS_ADD     = 'add';

    /**
     * Класс условия, используется для создания себе подобных
     *
     * @var string
     */
    private $condClass = '';

    /**
     * Название сущноси или альяс или псевдосущность
     * @var string
     */
    protected $name;

    protected $entityName;

    protected $entityVar;

    /**
     * @var string
     */
    protected $entityClassName;

    /**
     * @var string
     */
    protected $collectionClassName;

    /**
     * Хранилище условий
     * @var array
     */
    protected $_params = array('cond' => array(),
                               'join' => array(),
                               'with' => array(),
                               'param' => array());

    protected $_joinRegistry = array();

    /**
     * Были ли ошибки в процессе работы
     *
     * @var $_isError boolean
     */
    protected $_isError = false;

    /**
     * Доступные типы JOIN
     *
     * @var array
     */
    public static $_joinTypes = array(
        self::JOIN_INNER    => 'join',
        self::JOIN_LEFT     => 'joinLeft',
        self::JOIN_RIGHT    => 'joinRight',
        self::JOIN_CROSS    => 'joinCross',
        self::JOIN_FULL     => 'joinFull',
        self::JOIN_NATURAL  => 'joinNatural'
    );

    /**
     * Присутствует ли в условии JOIN
     *
     * @var boolean
     */
    protected $_joinExistsFlag = false;

    /**
     * Массив параметров для UNION запросов
     *
     * @var AbstractCond[]
     */
    protected $_unionCondList = null;

    //protected $_mysqlSelectAdapter = null;

    //protected $_mongoSelectAdapter = null;

    /**
     * @var AbstractCond
     */
    protected $parent = null;

    /**
     * @var AbstractCond[]
     */
    protected $childList = array();

    public function __construct($name = null, $type = null)
    {
        $this->setupEntity();

        if ($name) {
            $this->setName($name);
        }

        /**
         * Если entityClassName не определен,
         * то строим его из типа данных
         */
        if (!$this->entityClassName && $type) {
            $type = implode('', array_map('ucfirst', explode('_', $type)));
            $this->setEntityClassName('\\Model\\Entity\\' . $type . 'Entity');
        }

        if (!$this->collectionClassName && $type) {
            $type = implode('', array_map('ucfirst', explode('_', $type)));
            $this->setCollectionClassName('\\Model\\Collection\\' . $type . 'Collection');
        }

        if ($type) {
            $this->condClass = self::getClassNameByType($type);
        } else {
            $this->condClass = get_called_class();
        }

        if (empty($this->name)) {
            throw new \Model\Exception\ErrorException('Name is undefined');
        }
    }

    public function getCondClass()
    {
        return $this->condClass;
    }

    /**
     * Инициализация Entity
     */
    protected function setupEntity()
    {}

    /**
     * Установить новую сущность
     *
     * @param string $name
     * @param bool   $isForce
     * @return AbstractCond
     */
    public function setName($name, $isForce = false)
    {
        if (empty($this->name) || !$isForce) {
            $this->name = (string)$name;
        }
    	return $this;
    }

    /**
     * @return mixed
     */
    public function getEntityVar()
    {
        if (!$this->entityVar) {
            $this->setEntityName();
        }

        return $this->entityVar;
    }

    /**
     * @param null|string $name
     * @return $this
     */
    public function setEntityName($name = null)
    {
        if ($name === null) {
            $name = $this->getName();
        }

        $this->entityVar = preg_replace('#(Collection|_collection|Count|_count)$#s', '', $name);
        $this->entityName = implode('', array_map('ucfirst', explode('_', $this->entityVar)));
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntityName()
    {
        if (!$this->entityName) {
            $this->setEntityName();
        }

        return $this->entityName;
    }

    /**
     * Получить значение Entity
     *
     * @return string
     */
    public function getName()
    {
        return (string)$this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setEntityClassName($name)
    {
        $this->entityClassName = (string)$name;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntityClassName()
    {
        if (empty($this->entityClassName)) {
            $this->entityClassName = '\\Model\\Entity\\' . $this->getEntityName() . 'Entity';
        }

        return $this->entityClassName;
    }

    public function setCollectionClassName($name)
    {
        $this->collectionClassName = (string)$name;
        return $this;
    }

    /**
     * @return string
     */
    public function getCollectionClassName()
    {
        if (empty($this->collectionClassName)) {
            $this->collectionClassName = '\\Model\\Collection\\' . $this->getEntityName() . 'Collection';
        }

        return $this->collectionClassName;
    }

    /**
     *
     */
    public function __clone()
    {
        if (array_key_exists('default', $this->_params['cond'])) {
            $this->_params['cond']['default'] = clone $this->_params['cond']['default'];
        }

		if (isset($this->_params['join'])) {
			foreach ($this->_params['join'] as $id => $join) {
				$this->_params['join'][$id] = clone $join;
			}
		}
    }

    /**
     * Установить флаг об ошибке
     *
     * @param bool $flag
     */
    public function setErrorFlag($flag = true)
    {
        $this->_isError = (bool)$flag;
    }

    /**
     * Получить флаг ошибки
     *
     * @return bool
     */
    public function isError()
    {
        return (bool)$this->_isError;
    }

    /************************************************************************
     * НЕОБХОДИМЫЕ МЕТОДЫ
     ************************************************************************/
    /**
     * Установить COND limit
     * @param int $limit количество выбираемых элементов
     * @param int|bool $offset смещение от начала
     * @return AbstractCond
     */
    public function limit($limit, $offset = false)
    {
        $this->cond(AbstractCond::LIMIT, $limit);

        if ($offset !== false) {
            $this->offset($offset);
        }

        return $this;
    }

    /**
     * Установить COND offset
     * @param int $offset
     * @return AbstractCond
     */
    public function offset($offset)
    {
        return $this->cond(AbstractCond::OFFSET, $offset);
    }

    /**
     * Включить/Выключить
     *
     * @param boolean $distinct
     * @return AbstractCond
     */
    public function distinct($distinct = true)
    {
        return $this->cond(AbstractCond::DISTINCT, $distinct);
    }

    /**
    * @param int $page
    * @param int|bool $itemsPerPage
    * @return AbstractCond
    */
    public function page($page, $itemsPerPage = false)
    {
        if ($page === false || is_null($page)) {
            unset($this->_params['cond'][AbstractCond::PAGE], $this->_params['cond'][AbstractCond::ITEMS_PER_PAGE]);
        } else {
            $this->cond(AbstractCond::PAGE, $page);

            if ($itemsPerPage !== false && !is_null($itemsPerPage)) {
                $this->cond(AbstractCond::ITEMS_PER_PAGE, $itemsPerPage);
            }
        }

        return $this;
    }

	/**
	 * @param string|array $columns
	 * @param string $type
	 * @return AbstractCond
	 */
    public function columns($columns, $type = self::COLUMNS_REPLACE)
    {
        if (self::COLUMNS_ADD == $type) {
            $_columns = $this->getCond(AbstractCond::COLUMNS);
            if ($_columns && is_scalar($_columns)) {
                $_columns = (array)$_columns;
            }
            if (!$_columns || !is_array($_columns)) {
                $_columns = array('*');
            }

            $columns = array_merge($_columns, (array)$columns);
        } elseif (is_array($columns) && count($columns) == 1) {
            $columns = reset($columns);
        }

        if (is_null($columns) || $columns == '*') {
            if (isset($this->_params['cond'][AbstractCond::COLUMNS])) {
                unset($this->_params['cond'][AbstractCond::COLUMNS]);
            }
            return $this;
        }

        return $this->cond(AbstractCond::COLUMNS, $columns);
    }

    /**
    * @param mixed $order
    * @return AbstractCond
    */
    public function order($order)
    {
        $key = sha1(serialize($order));

        if (!array_key_exists($key, @$this->_params['cond'][AbstractCond::ORDER] ?: array())) {
            $this->_params['cond'][AbstractCond::ORDER][$key] = $order;
        }

        return $this;
    }

	/**
	 * @param $from
	 *
	 * @return AbstractCond
	 */
    public function from($from)
    {
        if (!isset($this->_params['cond'][AbstractCond::FROM])) {
            $this->_params['cond'][AbstractCond::FROM] = $from;
        }

    	return $this;
    }

    /**
     * Условия выборки WHERE
     *
     * @param      $where
     * @param null $bindParams
     * @return AbstractCond
     */
    public function where($where, $bindParams = null)
    {
        $where = array('cond' => $where,
                       'bind' => $bindParams);

        $key = sha1(serialize($where));

        if (!isset($this->_params['cond'][AbstractCond::WHERE][$key])) {
            $this->_params['cond'][AbstractCond::WHERE][$key] = $where;
        }

        return $this;
    }

    /**
     * Условия выборки HAVING
     *
     * @param string   $cond  The HAVING condition.
     * @param mixed    $value OPTIONAL The value to quote into the condition.
     * @param mixed $type  OPTIONAL The type of the given value
     *
     * @return AbstractCond
     */
    public function having($cond, $value = null, $type = null)
    {
        $having = array('cond' => $cond,
                       'value' => $value,
                       'type' => $type);
        $key = sha1(serialize($having));

        if (!isset($this->_params['cond'][AbstractCond::HAVING][$key])) {
            $this->_params['cond'][AbstractCond::HAVING][$key] = $having;
        }

        return $this;
    }


    /**
    * @param mixed $group
    * @return AbstractCond
    */
    public function group($group)
    {
        $key = sha1($group);

        if (!isset($this->_params['cond'][AbstractCond::GROUP][$key])) {
            $this->_params['cond'][AbstractCond::GROUP][$key] = $group;
        }

    	return $this;
	}

    /**
     * Добавить параметры UNION запроса
     *
     * @param AbstractCond $opts
     * @return AbstractCond
    public function addUnionCond(AbstractCond $opts)
    {
        if ($this->_unionCondList === null) {
            $this->_unionCondList = array();
        }
        $this->_unionCondList[] = $opts;
        $this->type(Model_Abstract::FETCH_UNION);
        return $this;
    }
     */

    /**
     * Получить параметры UNION запросов
     *
     * @return AbstractCond[]
     */
    public function getUnionCondList()
    {
        return $this->_unionCondList ?: array();
    }

    /**
     * Удалить параметры UNION запросов
     *
     * @return AbstractCond
     */
    public function deleteUnionCondList()
    {
        $this->_unionCondList = null;
        return $this;
    }

    /**
    * @param mixed $type
    * @return AbstractCond
    */
    public function type($type = AbstractCond::FETCH_ROW)
    {
        return $this->cond('type', $type);
    }

    public function getType()
    {
        return isset($this->_params['cond']['type']) ? $this->_params['cond']['type'] : AbstractCond::FETCH_ROW;
    }

    /**
    * @param mixed $name
    * @param mixed $value
    * @return AbstractCond
    */
    public function param($name, $value)
    {
        $this->_params['param'][$name] = $value;
        return $this;
    }

    public function checkParam($name)
    {
        return array_key_exists($name, $this->_params['param']);
    }

    public function getParam($name, $default = null)
    {
        if ($this->checkParam($name)) {
            return $this->_params['param'][$name];
        } else {
            return $default;
        }
    }

    /**
     * Добавить WITH
     *
     * @param AbstractCond|string $cond
     * @param null                $type
     *
     * @throws Exception\ErrorException
     * @return AbstractCond
     */
    public function with($cond, $type = null)
    {
        if (is_scalar($cond)) {
            $cond = AbstractModel::condFactory($cond, $type);
        } elseif (!$cond instanceof AbstractCond) {
            throw new ErrorException('Cond must be instance of Cond');
        }

        $this->_params['with'][$cond->getName()] = $cond;
        return $this;
    }

	/**
	 * Проверить существование присоединения WITH
     *
	 * @param string $entity
	 * @return boolean
	 */
    public function checkWith($entity)
    {
        return isset($this->_params['with'][$entity]);
    }

    /**
     * Удалить WITH
     *
     * @param string $entity
     * @return void
     */
    public function deleteWith($entity)
    {
        unset($this->_params['with'][$entity]);
    }

    /**
     * Получить присоединение with
     *
     * @param      $entity
     * @param null $type
     * @return AbstractCond
     */
    public function getWith($entity, $type = null)
    {
        if (!$this->checkWith($entity)) {
            $this->_params['with'][$entity] = AbstractModel::condFactory($entity, $type);
        }

        return $this->_params['with'][$entity];
    }

    /**
     * Получить все WITH параметры
     *
     * @return array
     */
    public function getWithParams()
    {
        return $this->_params['with'];
    }

    /**
     * Присоединить
     *
     * @param $entity
     * @param string $joinType Cond::JOIN_*
     * @return AbstractCond
     */
    public function join($entity, $joinType = self::JOIN_INNER)
    {
        $join = new Join($entity, $joinType);
        $this->_params[self::JOIN][] = $join;
        $this->_joinRegistry[$joinType][$entity][] = $join;
        $this->_joinExistsFlag = true;
        return $this;
    }

    /**
     * Присоединить слева
     *
     * @param $entity
     * @return AbstractCond
     */
    public function joinLeft($entity)
    {
    	return $this->join($entity, self::JOIN_LEFT);
    }

    /**
     * Проверить существование JOIN
     * @param string $entity
     * @param string $joinType Cond::JOIN_*
     * @return boolean
     */
    public function checkJoin($entity, $joinType = self::JOIN_INNER)
    {
        if (!$this->checkAnyJoin()) {
            return false;
        }

        if ($joinType == self::JOIN_CHECK_ANY) {
            return $this->checkAnyJoin();
        } elseif ($joinType == self::JOIN_CHECK_ANY_FOR_ENTITY) {
            $types = array_keys(self::$_joinTypes);
            foreach ($types as $type) {
                if ($this->checkJoin($entity, $type)) {
                    return true;
                }
            }
            return false;
        } else {
            return isset($this->_joinRegistry[$joinType][$entity]);
        }
    }

    /**
     * Был ли вообще join
     *
     * @return boolean
     */
    public function checkAnyJoin()
    {
        return $this->_joinExistsFlag;
    }

    /**
     * Убрать JOIN
     *
     * @return AbstractCond
     */
    public function deleteJoin()
    {
        $this->_params[self::JOIN] = array();

        $this->_joinRegistry = array();

        $this->_joinExistsFlag = false;
        return $this;
    }


    /**
     * Правила присоединения прописываются в моделях.
     * Эти правила накладываются на сущесвующий правила join,
     * но если таковые отсутствуют создается новое проавило join
     *
     * @param mixed  $entity
     * @param mixed  $joinType
     * @param mixed  $joinTable
     * @param string $joinCond
     * @param string $joinCols
     * @param string $insertPositionType
     * @param null   $relativeJoin
     * @throws \Model\Exception\ErrorException
     * @param mixed  $relativeJoin
     * @return AbstractCond
     */
    public function joinRule($entity, $joinType, $joinTable, $joinCond = '', $joinCols = '', $insertPositionType = AbstractCond::INSERT_JOIN_INSTEAD, $relativeJoin = null)
    {
        $this->_joinExistsFlag = true;

        if ($insertPositionType != self::INSERT_JOIN_INSTEAD && ($relativeJoin || $insertPositionType == self::INSERT_JOIN_END)) {
            $insertedJoin = new JoinCond($entity, $joinType);
            $insertedJoin->setRule($joinTable, $joinCond, $joinCols);

            switch ($insertPositionType) {
                case self::INSERT_JOIN_END:
                    $this->_params[self::JOIN][] = $insertedJoin;
                    break;
                case self::INSERT_JOIN_BEFORE:
                    $newParamsArray = array();

                    foreach ($this->_params[self::JOIN] as $v) {
                        if ($v == $relativeJoin) {
                            $newParamsArray[] = $insertedJoin;
                        }
                        $newParamsArray[] = $v;
                    }

                    $this->_params[self::JOIN] = $newParamsArray;
                    break;
                case self::INSERT_JOIN_AFTER:
                    $newParamsArray = array();

                    foreach ($this->_params[self::JOIN] as $v) {
                        $newParamsArray[] = $v;

                        if ($v == $relativeJoin) {
                            $newParamsArray[] = $insertedJoin;
                        }
                    }
                    $this->_params[self::JOIN] = $newParamsArray;
                    break;
            }

            $this->_joinRegistry[$joinType][$entity][] = $insertedJoin;

        } elseif ($insertPositionType == self::INSERT_JOIN_INSTEAD) {
            if (!isset($this->_joinRegistry[$joinType][$entity])) {
                $this->join($entity, $joinType);
            }

            /** @var $join \Model\Cond\Join */
            foreach ($this->_joinRegistry[$joinType][$entity] as $join) {
                if (!$join->issetRule()) {
                    $join->setRule($joinTable, $joinCond, $joinCols);
                }
            }
        } else {
            throw new \Model\Exception\ErrorException("Unknown insert position type");
        }

        return $this;
    }

    /**
     *
     * @return \Model\Cond\Join[]
     */
    public function getJoin()
    {
        return $this->_params[self::JOIN];
    }

    /**
     * Условие
     * @param $name
     * @param $value
     *
     * @throws \Model\Exception\ErrorException
     * @return AbstractCond
     */
    public function cond($name, $value = null)
    {
        if ($name instanceof Select) {
            throw new \Model\Exception\ErrorException('Oops! Bomb has been planted, boooooooom! Ctrl+F for string: ZendDbSelectRemoveFromHereNafik!');
        } else {
            $this->_params['cond'][$name] = $value;
        }

        return $this;
    }

    /**
     * Удалить условие
     *
     * @param string $name
     * @return AbstractCond
     */
    public function deleteCond($name)
    {
        if (array_key_exists($name, $this->_params['cond'])) {
            unset($this->_params['cond'][$name]);
        }

        return $this;
    }

    /**
     * Проверить наличие условия(й)
     * @param mixed $name Может быть массивом
     * @param bool  $default
     * @return bool
     */
    public function checkCond($name, $default = false)
    {
        if (!is_array($name)) {
            if (array_key_exists($name, $this->_params['cond'])) {
                return true;
            } else {
                return $default;
            }
        }

        foreach ($name as $n) {
            if (array_key_exists($n, $this->_params['cond'])) {
                return true;
            }
        }

        return $default;
    }

    /**
     * Получить значение условия
     *
     * @param string $name
     * @param mixed  $default
     * @throws \Model\Exception\ErrorException
     * @return mixed
     */
    public function getCond($name, $default = false)
    {
        if (is_array($name)) {
            throw new \Model\Exception\ErrorException('Array param is deprecated in getCond()');
        }

        if ($name == 'default') {
            throw new \Model\Exception\ErrorException('Boom');
        }

        if (array_key_exists($name, $this->_params['cond'])) {
            return $this->_params['cond'][$name];
        }

        return $default;
    }

    /**
     * Return condition as an array
     *
     * @return array
     */
    public function toArray()
    {
        $entity = $this->getEntityVar();

        $result = array();

        if (isset($this->_params['cond']) && is_array($this->_params['cond'])) {
            foreach ($this->_params['cond'] as $name => $value) {
                $_key = 'cond__' . $entity . (($name != 'default') ? '__' . $name : '');
                $result[$_key] = $value;
            }
        }

        foreach (self::$_joinTypes as $joinType) {
	        if (isset($this->_params[$joinType]) && is_array($this->_params[$joinType])) {
	            foreach ($this->_params[$joinType] as $name => $value) {
	                $_key = $joinType . '__' . $entity . '__' . $name;
	                $result[$_key] = $value;
	            }
	        }
        }

        if (isset($this->_params['with']) && is_array($this->_params['with'])) {
            /** @var $value AbstractCond */
            foreach ($this->_params['with'] as $name => $value) {
                $_key = 'with__' . $entity . '__' . $name;
                $result[$_key] = true;

                $result = array_merge($result, $value->toArray());
            }
        }

        return $result;
    }

    /**
     * Получить пустой элемент в зависимости от контекста Model_Abstract::FETCH_
     *
     * @return mixed
     */
    public function getEmptySelectResult()
    {
    	switch ($this->getCond ('type', AbstractCond::FETCH_ROW)) {
    		case AbstractCond::FETCH_ALL:
                if ($this->checkCond(AbstractCond::WITHOUT_PREPARE)) {
                    return array();
                }
    			$obj = '\\Model\\Collection\\' . $this->getEntityName() . 'Collection';
    			return new $obj();
    		case AbstractCond::FETCH_ROW:
                if ($this->checkCond(AbstractCond::WITHOUT_PREPARE)) {
                    return array();
                }
    			$obj = '\\Model\\Entity\\' . $this->getEntityName() . 'Entity';
    			return new $obj();
    		case AbstractCond::FETCH_COUNT:
    			return 0;
    		case AbstractCond::FETCH_ONE:
    		case AbstractCond::FETCH_NONE:
    			return false;
    		case AbstractCond::FETCH_PAIRS:
                return array();
    	}

	    return array();
    }

    /**
     * Показать выполняемый запрос
     * @param bool $extended В расширенном виде
     * @return AbstractCond
     */
    public function showQuery($extended = false)
    {
        $name = $extended ? self::SHOW_QUERY_EXTENDED : self::SHOW_QUERY;

        return $this->cond($name, true);
    }

    /**
     * Нах препаре
     * @param bool $flag
     * @return AbstractCond
     */
    public function withoutPrepare($flag = true)
    {
        return $this->cond(self::WITHOUT_PREPARE, $flag);
    }

    /**
     * Создать самого себя
     *
     * @param null $entity
     * @param null $type
     * @return AbstractCond
     */
    public static function init($entity = null, $type = null)
    {
        if ($type) {
            $class = self::getClassNameByType($type);
        } else {
            $class = get_called_class();
        }

        return new $class($entity, $type);
    }

    /**
     * @param $type
     * @return string
     */
    public static function getClassNameByType($type)
    {
        $class = implode('', array_map('ucfirst', explode('_', $type)));

        if (substr($class, -4) != 'Cond') {
            $class .= 'Cond';
        }

        if (substr($class, 0, 12) != '\\Model\\Cond\\') {
            $class = '\\Model\\Cond\\' . $class;
        }

        if (!class_exists($class)) {
            $class = '\\Model\\Cond\\Cond';
        }

        return $class;
    }

    /**
     * Получить хеш кондиции
     *
     * @return string
     */
    public function getHash()
    {
        $arr = $this->_params;
        unset($arr['with']);
        arsort($arr);
        reset($arr);
        $additional = '';
        if ($this->_unionCondList) {
            foreach ($this->getUnionCondList() as $_cond) {
                $additional .= $_cond->getHash();
            }
        }
        return sha1($this->name . serialize($arr) . $additional);
    }

    /**
     * Underscore to CamelCase transform
     *
     * @param $str
     * @return string
     */
    protected function _underscoreToCamelCaseFilter($str)
    {
        return implode('', array_map('ucfirst', explode('_', $str)));
    }

    /**
     * Установить флаг разрешающий обновление
     *
     * @param bool $flag
     * @return \Model\Cond\AbstractCond
     */
    public function setUpdateAllowed($flag = true)
    {
        $this->cond(self::UPDATE_ALLOWED, (bool)$flag);
        return $this;
    }

    /**
     * Получить флаг разрешающий обновление, по-умолчанию false
     *
     * @return bool
     */
    public function isUpdateAllowed()
    {
        if (!$this->checkCond(self::UPDATE_ALLOWED) && $this->parent) {
            $this->cond(self::UPDATE_ALLOWED, $this->parent->isUpdateAllowed());
        }

        return $this->getCond(self::UPDATE_ALLOWED, true);
    }

    /**
     * Установить флаг разрешающий обработку вложенных сущностей
     *
     * @param bool $flag
     * @return \Model\Cond\AbstractCond
     */
    public function setCascadeAllowed($flag = true)
    {
        $this->cond(self::CASCADE_ALLOWED, (bool)$flag);
        return $this;
    }

    /**
     * Получить флаг разрешающий обработку вложенных сущностей, по-умолчанию true
     *
     * @return bool
     */
    public function isCascadeAllowed()
    {
        if (!$this->checkCond(self::CASCADE_ALLOWED) && $this->parent) {
            $this->cond(self::CASCADE_ALLOWED, $this->parent->isCascadeAllowed());
        }

        return $this->getCond(self::CASCADE_ALLOWED, true);
    }

    /**
     * Установить флаг разрешающий работу при ошибках, только на иморте
     *
     * @param bool $flag
     * @return \Model\Cond\AbstractCond
     */
    public function setIgnoreErrors($flag = true)
    {
        $this->cond(self::IGNORE_ERRORS, (bool)$flag);
        return $this;
    }

    /**
     * Проверить, что нужно игнорить ошибки при импорте
     *
     * @return bool
     */
    public function isIgnoreErrors()
    {
        if (!$this->checkCond(self::IGNORE_ERRORS) && $this->parent) {
            $this->cond(self::IGNORE_ERRORS, $this->parent->isIgnoreErrors());
        }

        return $this->getCond(self::CASCADE_ALLOWED, false);
    }

    /**
     * Установить флаг разрешающий добавлять связи, только на иморте
     *
     * @param bool $flag
     * @return \Model\Cond\AbstractCond
     */
    public function setAppendLink($flag = true)
    {
        $this->cond(self::APPEND_LINK, (bool)$flag);
        return $this;
    }

    /**
     * Проверить, что нужно игнорить ошибки при импорте
     *
     * @return bool
     */
    public function isAppendLink()
    {
        if (!$this->checkCond(self::APPEND_LINK) && $this->parent) {
            $this->cond(self::APPEND_LINK, $this->parent->isAppendLink());
        }

        return $this->getCond(self::APPEND_LINK, true);
    }

    /**
     * @param string       $name
     * @param AbstractCond $cond
     * @return AbstractCond
     */
    public function addChild($name, AbstractCond $cond)
    {
        $cond->setParent($this);
        $this->childList[(string)$name] = $cond;
        return $this;
    }

    /**
     * Получить вложенный
     *
     * @param $name
     * @return AbstractCond
     */
    public function getChild($name)
    {
        if (!isset($this->childList[$name])) {
            $this->addChild($name, AbstractModel::condFactory($name));
        }

        return $this->childList[$name];
    }

    /**
     * Удалить вложенный
     *
     * @param AbstractCond|string $name
     * @return AbstractCond
     */
    public function removeChild($name)
    {
        if ($name instanceof AbstractCond) {
            $this->childList = array_diff($this->childList, array($name));
        } else {
            unset($this->childList[$name]);
        }

        return $this;
    }
    /**
     * @param AbstractCond $cond
     * @return AbstractCond
     */
    public function setParent(AbstractCond $cond)
    {
        $this->parent = $cond;
        return $this;
    }

    /**
     * @return AbstractCond
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return AbstractCond
     */
    public function hasParent()
    {
        return !is_null($this->parent);
    }

}