<?php

namespace Model\Cluster\Schema\Table;

use ArrayObject;
use Model\Cluster\Schema\Table as Table;
use Model\Exception\ErrorException;

/**
 * Колонка таблицы
 *
 * Обладает следующими свойствами:
 *  - primary
 *  - nullable
 *  - unique
 *  - может обладать значение по-умолчанию
 *  - может быть в составе инедксов
 *  - может участвовать в связях
 *
 * @category   Model
 * @package    Model_Schema
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Column extends ArrayObject
{
    /**
     * @var Table
     */
    protected $_table;

    /**
     * Список фильтров
     *
     * @var array
     */
    private $filterArray = array();

    /**
     * Список валидаторов
     *
     * @var array
     */
    private $validatorArray = array();

    /**
     * Список декораторов
     *
     * @var array
     */
    private $decoratorArray = array();

    /**
     * @param array $data
     * @param Table $table
     */
    public function __construct(array $data, Table $table)
    {
        $this->_table = $table;
        parent::__construct($this->prepareData($data), ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Инициализация
     *
     * @return Column
     */
    public function init()
    {
        $this->setupDefaultValidators();
        $this->setupDefaultFilters();
        $this->setupDefaultDecorators();
        return $this;
    }

    /**
     * Создать колонку из XML
     *
     * @param                             $xml
     * @param \Model\Cluster\Schema\Table $table
     * @return Column
     */
    public static function fromXml($xml, Table $table)
    {
        if (is_array($xml)) {
            $data = $xml;
        } else {
            $xml = simplexml_load_string($xml);
            $data = json_decode(json_encode((array) $xml), 1);
        }
        $data = self::prepareXmlArray($data);

        $column = new Column($data['data'], $table);

        if (isset($data['filters'])) {
            $filters = is_int(key($data['filters']['filter'])) ? $data['filters']['filter'] : $data['filters'];

            foreach ($filters as $filter) {
                $column->addFilter($filter['name'], $filter['params']);
            }
        }

        if (isset($data['validators'])) {
            $validators = is_int(key($data['validators']['validator'])) ? $data['validators']['validator'] : $data['validators'];
            foreach ($validators as $validator) {
                $column->addValidator($validator['name'], $validator['params']);
            }
        }

        if (isset($data['decorators']['decorator'])) {
            $decorators = is_int(key($data['decorators']['decorator'])) ? $data['decorators']['decorator'] : $data['decorators'];
            foreach ($decorators as $decorator) {
                $column->addDecorator($decorator['name']);
            }
        }

        return $column;
    }

    public static function structureXmlArray($data)
    {
        foreach ($data as $k => &$v) {
            if (is_array($v) && !is_int($k) && in_array($k, array('column', 'table', 'schema', 'index', 'link', 'filter', 'decorator', 'validator')) && !is_int(key($v))) {
                unset($data[$k]);
                $data[$k][] = $v;
            }

            if (is_array($v)) {
                $v = self::structureXmlArray($v);
            }
        }

        return $data;
    }

    /**
     * Обработать массим который пришел из XML
     *
     * @param $data
     * @return mixed
     */
    public static function prepareXmlArray($data)
    {
        $data = self::structureXmlArray($data);

        foreach ($data as $k => &$v) {
            if (is_array($v) && empty($v)) {
                $v = null;
            } elseif (is_array($v)) {
                $v = self::prepareXmlArray($v);
            }

            if (substr($k, 0, 10) == 'array_key_') {
                $data[intval(substr($k, 10))] = $v;
                unset($data[$k]);
            }
        }

        return $data;
    }

    /**
     * Set column data
     *
     * @param array $data
     * @return array
     */
    protected function prepareData(array $data)
    {
        $result = array();

        if ($data) {
            $defaults = array('table_catalog'            => null,
                              'table_schema'             => $this->getTable()->getSchema()->getName(),
                              'table_name'               => $this->getTable()->getName(),
                              'column_name'              => '', // имя колонки
                              'ordinal_position'         => 1, // позиция в таблице
                              'column_default'           => null, // значение по умолчанию
                              'is_nullable'              => false, // может иметь нулевое значение
                              'is_autoincrement'         => false, // autoincrement
                              'data_type'                => null, // тип данных
                              'character_maximum_length' => null, // длинна данных в симовлах
                              'character_octet_length'   => null, // длинна поля
                              'numeric_precision'        => null, // количество знаков до запятой
                              'numeric_scale'            => null, // количество знаков после запятой
                              'character_set_name'       => null, // Какая-то кодировочка
                              'collation_name'           => null, // в какой кодировке храним
                              'column_type'              => null, // тип колоки, например, int(11) unsigned
                              'column_comment'           => null, // комментарий
                              'is_unique'                => false // уникальное
            );

            foreach ($data as $k => &$value) {
                $data[strtolower($k)] = $value;
            }

            foreach ($defaults as $key => $default) {
                if (array_key_exists($key, $data)) {
                    switch ($key) {
                        case 'is_nullable':
                            $result[$key] = !is_bool($data[$key]) ? ($data[$key] == '1' || $data[$key] == 'YES') : $data[$key];
                            break;
                        case 'is_unique':
                            $result[$key] = !is_bool($data[$key]) ? ($data[$key] == '1' || $data[$key] == 'YES') : $data[$key];
                            break;
                        default:
                            $result[$key] = $data[$key];
                    }
                } else {
                    $result[$key] = $default;
                }
            }
        }

        if (isset($data['EXTRA']) && $data['EXTRA'] == 'auto_increment') {
            $result['is_autoincrement'] = true;
        }

        return $result;
    }

    /**
     * Добавить фильтр
     *
     * @param       $filterName
     * @param array $filterParams
     * @internal param $filter
     * @return Column
     */
    public function addFilter($filterName, $filterParams = array())
    {
        $filter['name'] = $filterName;
        $filter['params'] = empty($filterParams) ? null : $filterParams;
        $this->filterArray[] = $filter;

        return $this;
    }

    /**
     * Получить список фильтров или один фильтр по имени
     *
     * @param null $filter
     * @return array|bool
     */
    public function getFilter($filter = null)
    {
        if (is_null($filter)) {
            return $this->filterArray;
        } elseif (isset($this->filterArray[$filter])) {
            return $this->filterArray[$filter];
        } else {
            return false;
        }
    }

    /**
     * Добавить фильтр
     *
     * @param       $validatorName
     * @param array $validatorParams
     * @internal param $filter
     * @return Column
     */
    public function addValidator($validatorName, $validatorParams = array())
    {
        $filter['name'] = $validatorName;
        $filter['params'] = empty($validatorParams) ? null : $validatorParams;
        $this->validatorArray[] = $filter;

        return $this;
    }

    /**
     * Получить список валидаторов или один валидатор по имени
     *
     * @param null $validator
     * @return array|bool
     */
    public function getValidator($validator = null)
    {
        if (is_null($validator)) {
            return $this->validatorArray;
        } elseif (isset($this->validatorArray[$validator])) {
            return $this->validatorArray[$validator];
        } else {
            return false;
        }
    }

    /**
     * Добавить декоратор
     *
     * @param $decoratorName
     * @return Column
     */
    public function addDecorator($decoratorName)
    {
        $this->decoratorArray[$decoratorName] = array('name' => $decoratorName);

        return $this;
    }

    /**
     * Получить список декораторов или один декоратор по имени
     *
     * @param null $decorator
     * @return array|bool
     */
    public function getDecorator($decorator = null)
    {
        if (is_null($decorator)) {
            return $this->decoratorArray;
        } elseif (isset($this->decoratorArray[$decorator])) {
            return $this->decoratorArray[$decorator];
        } else {
            return false;
        }
    }

    /**
     * Установить флаг уникальности
     *
     * @param bool $uniqueFlag
     * @return \Model\Cluster\Schema\Table\Column
     */
    public function setUniqueFlag($uniqueFlag = true)
    {
        $this['is_unique'] = (bool)$uniqueFlag;
        return $this;
    }

    /**
     * Максимальная длинна в символах
     *
     * @return int
     */
    public function getCharacterMaximumLength()
    {
        return (int)$this['character_maximum_length'];
    }


    /**
     * Получить флаг уникальности
     *
     * @return boolean
     */
    public function isUnique()
    {
        return (boolean)$this['is_unique'];
    }

    /**
     * Is Autoincrement field
     *
     * @return bool
     */
    public function isAutoincrement()
    {
        return (boolean)$this['is_autoincrement'];
    }

    /**
     *
     * @return boolean
     */
    public function isNullable()
    {
        return $this['is_nullable'];
    }

    /**
     * Получить таблицу
     *
     * @return \Model\Cluster\Schema\Table
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * Get name of database
     *
     * @return string
     */
    public function getSchemaName()
    {
        return $this->getTable()->getSchema()->getName();
    }

    /**
     * Get name of column
     *
     * @return string
     */
    public function getName()
    {
        return $this['column_name'];
    }

    /**
     * @return string
     */
    public function getNameAsCamelCase()
    {
        return implode(array_map('ucfirst', explode('_', $this->getName())));
    }

    /**
     * Получить имя в виде CamelCase
     *
     * @return string
     */
    public function getNameAsVar()
    {
        $name = $this->getNameAsCamelCase();
        return strtolower($name[0]) . substr($name, 1);
    }

    /**
     * Получить имя сущности в CamelCase
     *
     * @return string
     */
    public function getEntityNameAsCamelCase()
    {
        $name = preg_replace('#_id$#', '', $this->getName());

        if ($name == 'id') {
            $name = $this->getTable()->getName();
        }

        return implode(array_map('ucfirst', explode('_', $name)));
    }

    /**
     * Представить в виде массива
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getArrayCopy();
    }

    /**
     * Получить тип колонки
     *
     * @return string
     */
    public function getColumnType()
	{
		$result = $this['column_type'];

		if (substr($this['column_type'], 0, 4) == 'enum') {
			$result = 'enum';
		} elseif (substr($this['column_type'], 0, 3) == 'set') {
			$result = 'set';
		} elseif (strpos($result, '(')) {
			$result =  substr($result, 0, strpos($result, '('));
		} elseif (strpos($result, ' ')) {
			$result =  substr($result, 0, strpos($result, ' '));
		}

		return $result;
	}

    /**
     * Проверить на целочисленность
     *
     * @return bool
     */
    public function isUnsigned()
    {
        return (strpos($this->getColumnType(), 'unsigned') !== FALSE);
    }

    /**
     * Получить значение ENUM
     *
     * @return string
     */
    public function getEnumValues()
    {
        return trim(substr($this['column_type'], 4), ' )(');
    }

    /**
     * Получить значение ENUM в виде массива
     *
     * @return array
     */
    public function getEnumValuesAsArray()
    {
        $str = trim(substr($this['column_type'], 4), ' )(');

        $params = explode(',', $str);

        foreach ($params as &$param) {
            $param = trim($param, ',\' ');
        }

        return $params;
    }

    /**
     * Получить комментарий
     *
     * @return string
     */
    public function getComment()
    {
        return (string)$this['column_comment'];
    }

    /**
     * Получить тип в типах-php
     *
     * @return string
     * @throws \Model\Exception\ErrorException
     */
    public function getTypeAsPhp()
	{
		$type = $this->getColumnType();

		switch ($type) {
			case 'string':
			case 'char':
			case 'varchar':
			case 'datetime':
			case 'blob':
			case 'enum':
			case 'text':
			case 'date':
			case 'longtext':
			case 'timestamp':
			case 'set':
				$result = 'string';
				break;
			case 'tinyint':
			case 'year':
			case 'smallint':
            case 'mediumint':
			case 'bigint':
			case 'int':
					$result = 'integer';
				break;
			case 'float':
			case 'decimal':
			case 'double':
					$result = 'float';
				break;
			default:
				throw new ErrorException("Unknown type '{$type}'");
		}

		return $result;
	}

    /**
     * Какой callbacl используется для обработки фильтр
     *
     * @return string
     * @throws \Model\Exception\ErrorException
     */
    public function getTypePrepareCallback()
    {
        $type = $this->getTypeAsPhp();

        switch ($type) {
            case 'string':
                $result = 'strval';
                break;
            case 'integer':
                $result = 'intval';
                break;
            case 'float':
                $result = 'floatval';
                break;
            default:
                throw new ErrorException("Unknown type '{$type}'");

        }

        return $result;
    }

    /**
     * Установить валидаторы по-умолчанию
     *
     * @return Column
     */
    public function setupDefaultFilters()
    {
        $name = $this->getName();
        switch ($this->getColumnType()) {
            case 'char':
            case 'varchar':
            case 'enum':
            case 'tinyblob':
            case 'tinytext':
            case 'blob':
            case 'text':
            case 'mediumblob':
            case 'mediumtext':
            case 'longblob':
            case 'longtext':
            case 'timestamp':
                if ($this->getColumnType() == 'enum') {
                    $this->addFilter('\App\Filter\EnumField');
                } elseif (substr($name, 0,3) == 'is_' && $this->getColumnType() == 'enum') {
                    $this->addFilter('\App\Filter\IsFlag');
                } elseif (substr($name, -5) == '_hash' || substr($name, -4) == '_md5') {
                    $this->addFilter('\App\Filter\Hash');
                } elseif (substr($name, -5) == '_stem' || $name == 'stem') {
                    $this->addFilter('\App\Filter\Stem');
                } elseif ($name == 'description' || $name == 'text' || substr($name, -12) == '_description' || substr($name, -5) == '_text') {
                    $this->addFilter('\App\Filter\Text');
                } elseif ($name == 'url' || substr($name, -4) == '_url') {
                    $this->addFilter('\App\Filter\Url');
                } elseif ($name == 'email' || substr($name, -6) == '_email') {
                    $this->addFilter('\App\Filter\Email');
                } elseif ($name == 'price' || substr($name, -6) == '_price') {
                    $this->addFilter('\App\Filter\Price');
                } elseif ($name == 'slug' || substr($name, -5) == '_slug') {
                    $this->addFilter('\App\Filter\Slug');
                } elseif (in_array($this->getColumnType(), array('varchar', 'char')) && ($this->getName() == 'name' || $this->getName() == 'name_alias' || $this->getName() == 'name_translate'
                    || $this->getName() == 'title' || $this->getName() == 'title_alias' || $this->getName() == 'title_translate'
                    || $this->getName() == 'h1' || $this->getName() == 'h1_alias' || $this->getName() == 'h1_translate'
                    || $this->getName() == 'meta_title' || $this->getName() == 'meta_title_alias' || $this->getName() == 'meta_title_translate')) {

                    $this->addFilter('\App\Filter\Name');
                } elseif ($this->getColumnType() == 'timestamp' || $this->getName() == 'date' || substr($this->getName(), -5) == '_date') {
                    $this->addFilter('\App\Filter\Date');
                } else {
                    $this->addFilter('\App\Filter\StringTrim');
                }
                break;
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'bigint':
                if ($name == 'level' || $name == 'pos' || $name == 'count' || substr($name, -6) == '_count') {
                    $this->addFilter('\App\Filter\Int');
                    $this->addFilter('\App\Filter\Abs');
                } elseif ($name = 'id' || substr($name, -3) == '_id') {
                    $this->addFilter('\App\Filter\Id');
                } else {
                    $this->addFilter('\Zend\Filter\Int');
                }

                break;
            case 'float':
            case 'decimal':
            case 'double':
                $this->addFilter('\Zend\Filter\Float');
                break;
        }

        return $this;
    }

    /**
     * Установить декораторы по-умолчанию
     *
     * @return Column
     */
    public function setupDefaultDecorators()
    {
        switch ($this->getColumnType()) {
            case 'char':
            case 'varchar':
            case 'tinyblob':
            case 'tinytext':
            case 'blob':
            case 'text':
            case 'mediumblob':
            case 'mediumtext':
            case 'longblob':
            case 'enum':
            case 'longtext':
                $this->addDecorator('String');
                if (substr($this->getName(), -4) == '_url') {
                    $this->addDecorator('Url');
                }
                break;
            case 'timestamp':
            case 'date':
                $this->addDecorator('DateTime');
                break;
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'bigint':
                $this->addDecorator('Int');
                break;
            case 'float':
            case 'decimal':
            case 'double':
                $this->addDecorator('Float');
                break;
        }

        return $this;
    }

    /**
     * Установить валидаторы по-умолчанию
     *
     * @return Column
     */
    public function setupDefaultValidators()
    {
        switch ($this->getColumnType()) {
            case 'char':
            case 'varchar':
                $maxLen = $this->getCharacterMaximumLength();

                if (substr($this->getName(), -5) == '_hash') {
                    $this->addValidator('\Zend\Validator\StringLength', array('min' => $maxLen, 'max' => $maxLen))
                         ->addValidator('\Zend\Validator\Hex');
                } elseif ($maxLen > 0) {
                    $this->addValidator('\Zend\Validator\StringLength', array('min' => 0, 'max' => $maxLen));
                }
                break;
            case 'enum':
                $enumValues = $this->getEnumValuesAsArray();
                $this->addValidator('\Zend\Validator\InArray', array('haystack' => $enumValues));
                break;
            case 'timestamp':
                $this->addValidator('\Zend\Validator\Date', array('format' => 'Y-m-d H:i:s'));
                break;
            case 'tinyblob':
            case 'tinytext':
                    $this->addValidator('\Zend\Validator\StringLength', array('min' => 0, 'max' => 255, 'encoding' => 'UTF-8'));
                break;
            case 'blob':
            case 'text':
                    $this->addValidator('\Zend\Validator\StringLength', array('min' => 0, 'max' => 65535, 'encoding' => 'UTF-8'));
                break;
            case 'mediumblob':
            case 'mediumtext':
                    $this->addValidator('\Zend\Validator\StringLength', array('min' => 0, 'max' => 16777215, 'encoding' => 'UTF-8'));
                break;
            case 'longblob':
            case 'longtext':
                break;
            case 'tinyint':
                if ($this->isUnsigned()) {
                    $min = 0;
                    $max = 255;
                } else {
                    $min = -128;
                    $max = 128;
                }
                $this->addValidator('\Zend\Validator\Regex', array('pattern' => '/^[\d]*$/'));
                $this->addValidator('\Zend\Validator\Between', array('min' => $min, 'max' => $max, 'inclusive' => true));
                break;
            case 'smallint':
                if ($this->isUnsigned()) {
                    $min = 0;
                    $max = 65535;
                } else {
                    $min = -32768;
                    $max = 32768;
                }
                $this->addValidator('\Zend\Validator\Regex', array('pattern' => '/^[\d]*$/'));
                $this->addValidator('\Zend\Validator\Between', array('min' => $min, 'max' => $max, 'inclusive' => true));
                break;
            case 'mediumint':
                if ($this->isUnsigned()) {
                    $min = 0;
                    $max = 16777215;
                } else {
                    $min = -8388607;
                    $max = 8388607;
                }
                $this->addValidator('\Zend\Validator\Regex', array('pattern' => '/^[\d]*$/'));
                $this->addValidator('\Zend\Validator\Between', array('min' => $min, 'max' => $max, 'inclusive' => true));
                break;
            case 'int':
            case 'bigint':
                $this->addValidator('\Zend\Validator\Regex', array('pattern' => '/^[\d]*$/'));
                break;
            case 'float':
            case 'decimal':
            case 'double':
                $this->addValidator('\Zend\Validator\Regex', array('pattern' => '/^[\d\,\.]*$/'));
                break;
        }

        return $this;
    }

    /**
     * Получить тип в виде константы
     *
     * @return string
     */
    public function getTypeAsEntityConstant()
	{
		$phpType = $this->getTypeAsPhp(true);

		switch ($phpType) {
			case 'string':
				$result = 'self::TYPE_STR';
				break;
			case 'uinteger':
			case 'integer':
				$result = 'self::TYPE_INT';
				break;
			case 'float':
			case 'ufloat':
				$result = 'self::TYPE_FLOAT';
				break;
			case 'boolean':
				$result = 'self::TYPE_BOOL';
				break;
			default:
				$result = 'self::TYPE_NONE';
				break;
		}

		return $result;
	}

    /**
     * Получить тип в виде DATA_TYPE константы
     *
     * @return bool|string
     */
    public function getTypeAsDataTypeConstant()
	{
		$phpType = $this->getTypeAsPhp(true);

		switch ($phpType) {
			case 'string':
				$result = 'self::DATA_TYPE_STRING';
				break;
			case 'uinteger':
			case 'integer':
				$result = 'self::DATA_TYPE_INT';
				break;
			case 'float':
			case 'ufloat':
				$result = 'self::DATA_TYPE_FLOAT';
				break;
			case 'boolean':
				$result = 'self::DATA_TYPE_BOOL';
				break;
			default:
				$result = false;
				break;
		}

		return $result;
	}

    /**
     * Получить значение по-умолчанию
     *
     * @return string
     */
    public function getColumnDefault()
    {
        return (string)$this['column_default'];
    }

    /**
     * Выгрузить в XML
     *
     * @param bool $withHeader
     * @param int  $tabStep
     * @return string
     */
    public function toXml($withHeader = true, $tabStep = 0)
    {
        $tab = '    ';
        $shift = str_repeat($tab, $tabStep);

        $xml = $withHeader ? \Model\Cluster::XML_HEADER . "\n": '';

        $xml .= $shift . '<column name="' . $this->getName() . '">' . "\n";
        $xml .= $shift . $tab . '<data>' . "\n";
        foreach ($this as $k => $v) {
            $xml .= $shift . $tab . $tab . '<' . $k . '>' . $v . '</' . $k . '>' . "\n";
        }
        $xml .= $shift . $tab . '</data>' . "\n";
        $xml .= $shift . $tab . '<filters>' . "\n";
        foreach ($this->filterArray as $filter) {
            $xml .= $shift . $tab . $tab . '<filter>' . "\n" . $this->arrayToXml($filter, $tabStep + 3) . $shift . $tab . $tab . '</filter>' . "\n";
        }
        $xml .= $shift . $tab . '</filters>' . "\n";

        $xml .= $shift . $tab . '<validators>' . "\n";
        foreach ($this->validatorArray as $validator) {
            $xml .= $shift . $tab . $tab . '<validator>' . "\n" . $this->arrayToXml($validator, $tabStep + 3) . $shift . $tab . $tab . '</validator>' . "\n";
        }
        $xml .= $shift . $tab . '</validators>' . "\n";

        $xml .= $shift . $tab . '<decorators>' . "\n";
        foreach ($this->decoratorArray as $decorator) {
            $xml .= $shift . $tab . $tab . '<decorator>' . "\n" . $this->arrayToXml($decorator, $tabStep + 3) . $shift . $tab . $tab . '</decorator>' . "\n";
        }
        $xml .= $shift . $tab . '</decorators>' . "\n";

        $xml .= $shift . '</column>' . "\n";

        return $xml;
    }

    /**
     * Преобразовать массив в XML
     *
     * @param     $array
     * @param int $tabStep
     * @return string
     */
    public function arrayToXml($array, $tabStep = 0)
    {
        $tab = '    ';
        $shift = str_repeat($tab, $tabStep);

        if (!is_array($array)) {
            return '';
        }

        $xml = '';
        foreach ($array as $k => $v) {
            if (is_numeric($k)) {
                $k = 'array_key_' . $k;
            }

            $xml .= $shift . '<' . $k . '>';
            if (is_array($v)) {
                $v = "\n" . $this->arrayToXml($v, $tabStep + 1) . $shift;
            }
            $xml .= $v . '</' . $k . '>' . "\n";
        }

        return $xml;
    }
}