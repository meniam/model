<?php

namespace Model\Schema;

use Model\Schema as Schema;
use Model\Schema\Table\Column as Column;
use Model\Schema\Table\Index\AbstractIndex;
use Model\Schema\Table\Link\AbstractLink;

use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;

class Table extends \ArrayIterator
{
    /**
     * Table name
     * 
     * @var string
     */
    protected $_name;
    
    /**
     * Schema
     * 
     * @var Schema
     */
    protected $_schema;
    
    /**
     * Fields registry where key is a name
     * 
     * @var array of Model_Schema_Table_Field
     */
    protected $_columnByNameRegistry = array();
    
    protected $_indexList = array();
    
    protected $_linkList = array();
    
    public function __construct($name, Schema $schema)
    {
        $this->_name   = $name;
        $this->_schema = $schema;

        $fields = $this->_describeFields();

        $array = array();
        foreach ($fields as $field) {
            $tableColumn = new Column($field, $this);
            $array[] = $tableColumn;
            $this->_columnByNameRegistry[$tableColumn->getName()] = $tableColumn;
        }
        
        parent::__construct($array);
    }

    /**
     * Описание полей таблицы
     *
     * @return array
     */
    protected function _describeFields()
    {
        $schema = $this->getSchema()->getName();
        $table  = $this->getName();
        
        $sql = "SELECT *
                FROM `information_schema`.`columns`
	            WHERE `TABLE_SCHEMA` = '{$schema}'
                    AND `TABLE_NAME` = '{$table}'";

        return $this->getDb()->fetchAll($sql);
    }
    
    /**
     * Get table name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Получить имя в виде CamelCase
     *
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
     * Is curernt table - link table
     * 
     * @return boolean
     */
    public function isLinkTable()
    {
        return substr($this->getName(), -5) == '_link';
    }
    
    public function addIndex(AbstractIndex $index)
    {
        $this->_indexList[$index->getName()] = $index;

        /**
         * Колонке ставим флаг уникальности
         */
        if (!$index->isMultiple() && $index->isUnique()) {
            $index->current()->setUniqueFlag(true);
        }
        
        return $this;
    }
    
    public function getIndex($indexName = null)
    {
        if (!$indexName) {
            return $this->_indexList;
        } elseif ($indexName && isset($this->_indexList[$indexName])) {
            return $this->_indexList[$indexName];
        } else {
            return false;
        }
    }

    public function addLink(AbstractLink $link)
    {
        $this->_linkList[$link->getName()] = $link;
        return $this;
    }

    /**
     * @param null $linkName
     * @return \Model\Schema\Table\Link\AbstractLink|\Model\Schema\Table\Link\AbstractLink[]|false|array
     */
    public function getLink($linkName = null)
    {
        if (!$linkName) {
            return $this->_linkList;
        } elseif ($linkName && isset($this->_linkList[$linkName])) {
            return $this->_linkList[$linkName];
        } else {
            return false;
        }
    }
    
    /**
     * Get Schema
     *
     * @return Schema
     */
    public function getSchema()
    {
        return $this->_schema;
    }
    
    
    public function getDb()
    {
        return $this->getSchema()->getDb();
    }

    /**
     * Get column by name 
     * 
     * @param string $column
     * @return Column
     */
    public function getColumn($column = null)
    {
        if ($column) {
            if (isset($this->_columnByNameRegistry[$column])) {
                return $this->_columnByNameRegistry[$column];
            } else {
                return false;
            }
        } else {
            return $this->_columnByNameRegistry;
        }
    }

    public function toArray($deep = true)
    {
        $result = array('name' => $this->getName());
        
        $result['columns'] = array();
        
        foreach ($this as $column) {
            $result['columns'][] = $column->toArray($deep);
        }
        
        foreach ($this->_indexList as $index) {
            $result['indexes'][] = $index->toArray($deep);
        }

        foreach ($this->_linkList as $link) {
            $result['links'][] = $link->toArray($deep);
        }
        
        return $result;
    }

    public function getMaxColumnNameLength()
    {
        $result = 0;
        foreach ($this as $column) {
            $result = max($result, strlen($column->getName()));
        }

        return $result;
    }
}