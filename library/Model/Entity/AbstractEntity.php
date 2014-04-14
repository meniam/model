<?php

namespace Model\Entity;

use Model\Collection\AbstractCollection;
use Model\Exception\ErrorException;

/**
 * Абстрактный класс Entity
 *
 * @category   Model
 * @package    Entity
 * @author     Eugene Myazin <meniam@gmail.com>
 * @since      27.11.12 12:18
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class AbstractEntity extends \ArrayObject implements EntityInterface
{
    const DATA_TYPE_BOOL    = 'bool';
    const DATA_TYPE_INT     = 'int';
    const DATA_TYPE_FLOAT   = 'float';
    const DATA_TYPE_STRING  = 'string';
    const DATA_TYPE_ARRAY   = 'array';
    const DATA_TYPE_NULL    = 'null';

    /**
     * @var array
     */
    protected static $allowedTypes = array(
        self::DATA_TYPE_BOOL,
        self::DATA_TYPE_INT,
        self::DATA_TYPE_FLOAT,
        self::DATA_TYPE_STRING,
        self::DATA_TYPE_ARRAY,
        self::DATA_TYPE_NULL
    );

    /**
     * Fields data types
     *
     * @var array
     */
    protected $dataTypes;

    /**
     * Data was assigned automatically
     *
     * @var array
     */
    private $autoAssignedData;

    /**
     * Flag indicates empty entity
     *
     * @var bool
     */
    protected $isEmpty = true;

    /**
     * Converted fields marker
     *
     * @var array
     */
    protected $_convertedFields = array();

    /**
     * Конструктор
     *
     * @param array $values
     * @internal param array|mixed $data
     * @return AbstractEntity
     */
    public function __construct($values = array())
    {
        if ($values instanceof EntityInterface) {
            /** @var $values AbstractEntity */
            parent::__construct($values, \ArrayObject::ARRAY_AS_PROPS);

            $this->autoAssignedData = $values->autoAssignedData;
            $this->isEmpty          = $values->isEmpty;
            $this->dataTypes        = $values->dataTypes;
            $this->_convertedFields = $values->_convertedFields;
            return;
        }

        $this->setupDataTypes();

        if (is_array($values) && !empty($values)) {
            foreach ($values as $name => $value) {
                if (!isset($this->dataTypes[$name])) {
                    //continue;
                }
                $this[$name] = $value;
                $this->isEmpty = false;
            }
        }

        if (is_array($this->dataTypes)) {
            foreach (array_keys($this->dataTypes) as $name) {
                if (!isset($this[$name])) {
                    $this->autoAssignedData[$name] = 1;
                }
            }
        }
    }

    /**
     * Пока поддерживаются только декораторы
     *
     * @param $method
     * @param $params
     * @return mixed
     * @throws ErrorException
     */
    public function __call($method, $params)
    {
        if (preg_match('#(.*?)As(.*?Decorator)$#si', $method, $m)) {
            $baseMethod = $m[1];
            $decorator = '\\Model\Entity\\Decorator\\' . $m[2];
            return new $decorator($this->$baseMethod());
        } else {
            throw new ErrorException('Method "' . $method . '" do not exists');
        }
    }

    /**
     * @return void
     */
    protected function setupDataTypes()
    { }

    /**
     * Нужно для выбора объектов прямо из базы
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        if (isset($this->dataTypes[$key])) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * @return bool
     */
    public function exists()
    {
        /** @var $this AbstractEntity */
        return !$this->isEmpty;
    }

    /**
     * Get entity Id
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->get('id');
    }

    /**
     * Equals
     *
     * @param EntityInterface $value
     * @return bool
     */
    public function equals(EntityInterface $value)
    {
        return ($this->getId() == $value->getId());
    }

    /**
     * Вынуть элемент из данных
     *
     * @param      $name
     * @return null
     */
    public function get($name)
    {
        if (!isset($this->_convertedFields[$name])) {
            if (isset($this->dataTypes[$name])) {
                $this->convertToType($name);
            }

            $this->_convertedFields[$name] = true;
        }

        return isset($this[$name]) ? $this[$name] : null;
    }

    /**
     * Convert field name to defined type
     *
     * @param      $name
     * @param null $value
     */
    protected function convertToType($name, $value = null)
    {
        if (!$value) {
            if (!isset($this[$name])) {
                $value = null;
            } else {
                $value = $this[$name];
            }
        }

        $objName  = $this->dataTypes[$name];

        switch ($objName) {
            case self::DATA_TYPE_STRING:
                $this[$name] = (string)$value;
                break;
            case self::DATA_TYPE_ARRAY:
                $this[$name] = (array)$value;
                break;
            case self::DATA_TYPE_INT:
                $this[$name] = (int)$value;
                break;
            case self::DATA_TYPE_BOOL:
                $this[$name] = (bool)$value;
                break;
            case self::DATA_TYPE_FLOAT:
                $this[$name] = (float)$value;
                break;
            case self::DATA_TYPE_NULL:
                $this[$name] = null;
                break;
            default:
                if (class_exists($objName)) {
                    $this[$name] = new $objName($value);
                }
        }
    }

    /**
     * Возвращает только те данные которые есть в dataTypes
     *
     * @return array
     */
    public function toArrayDescribedWithoutRelated()
    {
        $data = $this->toArrayWithoutRelated();

        foreach ($data as $k => $v) {
            if (!isset($this->dataTypes[$k])) {
                unset($data[$k]);
            }
        }

        return $data;
    }

    /**
     * Преобразовать сущность в массив
     *
     * @return array
     */
    public function toArray()
    {
        $resultArray = $this->getArrayCopy();

        /** @var AbstractEntity|AbstractCollection $value */
        foreach($resultArray as $key => &$value) {
            $value = $this->get($key);

            if ($key[0] == '_' && is_object($value)) {
                $value = $value->toArray();
            }

            if ($key[0] == '_' && !isset($this[$key])) {
                unset($resultArray[$key]);
            }
        }

        return $resultArray;
    }

    /**
     * Преобразовать сущность в массив без зависимостей
     *
     * @return array
     */
    public function toArrayWithoutRelated()
    {
        $array = $this->toArray();

        foreach (array_keys($array) as $k) {
            if ($k[0] == '_') {
                unset($array[$k]);
            }
        }

        return $array;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return strval($this->getId());
    }
}