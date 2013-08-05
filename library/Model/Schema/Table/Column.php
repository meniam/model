<?php

namespace Model\Schema\Table;

use ArrayObject;
use Model\Schema\Table as Table;
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
     * @param array $data
     * @param Table $table
     */
    public function __construct(array $data, Table $table)
    {
        $this->_table = $table;
        parent::__construct($this->prepareData($data), ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Set column data
     *
     * @param array $data
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
                            $result[$key] = !is_bool($data[$key]) ? ($data[$key] == 'YES') : $data[$key];
                            break;
    /*                    case 'is_unique':
                            $result[$key] = !is_bool($data[$key]) ? ($data[$key] == 'YES') : $data[$key];
                            break;*/
                        default:
                            $result[$key] = $data[$key];
                    }
                } else {
                    $result[$key] = $default;
                }
            }
        }

        return $result;
    }

    /**
     * Установить флаг уникальности
     *
     * @param bool $uniqueFlag
     * @return \Model\Schema\Table\Column
     */
    public function setUniqueFlag($uniqueFlag = true)
    {
        $this['is_unique'] = (bool)$uniqueFlag;
        return $this;
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
     * @return \Model\Schema\Table
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
     * Получить комментарий
     *
     * @return string
     */
    public function getComment()
    {
        return (string)$this['column_comment'];
    }

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
}