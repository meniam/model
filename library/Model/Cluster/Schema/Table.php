<?php

namespace Model\Cluster\Schema;

use Model\Cluster\Schema as Schema;
use Model\Cluster\Schema\Table\Column as Column;
use Model\Cluster\Schema\Table\Index\AbstractIndex;
use Model\Cluster\Schema\Table\Link\AbstractLink;
use Model\Exception\ErrorException;

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

    /**
     * @var array|AbstractIndex[]
     */
    protected $_indexList = array();

    /**
     * @var array|AbstractLink[]
     */
    protected $_linkList = array();
    
    public function __construct($name, Schema $schema)
    {
        if (empty($name)) {
            throw new ErrorException('Name cant be empty');
        }
        $this->_name   = $name;
        $this->_schema = $schema;
        //parent::__construct($array);
    }

    /**
     * @return Table
     */
    public function init()
    {
        $fields = $this->_describeFields();
        foreach ($fields as $field) {
            $column = new Column($field, $this);
            $this->addColumn($column);
        }

        return $this;
    }

    /**
     * @param        $xml
     * @param Schema $schema
     * @return Table
     */
    public static function fromXml($xml, Schema $schema)
    {
        if (is_array($xml)) {
            $data = $xml;
        } else {
            $xml = simplexml_load_string($xml);
            $data = json_decode(json_encode((array) $xml), 1);
        }
        $data = Column::prepareXmlArray($data);

        $name = $data['@attributes']['name'];
        $table = new Table($name, $schema);

        $columns = is_int(key($data['columns']['column'])) ? $data['columns']['column'] : $data['columns'];
        if (is_array($columns)) {
            foreach ($columns as $col) {
                $column = Column::fromXml($col, $table);
                $table->addColumn($column);
            }
        }

        /*if (isset($data['links']['link']) && is_array($data['links']['link'])) {
            foreach ($data['links']['link'] as $linkArray) {
                $link = AbstractLink::fromXml($linkArray, $table);
                $table->addLink($link);
            }
        }

        if (isset($data['indexes']['index']) && is_array($data['indexes']['index'])) {
            foreach ($data['indexes']['index'] as $indexArray) {
                $index = AbstractIndex::fromXml($indexArray, $table);
                $table->addIndex($index);
            }
        }*/

        return $table;
    }

    /**
     *
     */
    public function getAliasLinkList()
    {
        $result = array();

        foreach ($this->getLink() as $link) {
            if ($link->getForeignTable() == $link->getLocalTable() && $link->getLocalColumn() == $link->getForeignColumn()) {
                continue;
            }

            if ($link->getLocalEntityAlias() != $link->getLocalTable()->getName()) {
                $result[$link->getLocalEntityAlias()] = $this->getName();
            }
        }

        return $result;
    }

    /**
     * @param $field
     * @return bool
     */
    public function isFieldInForeignKey($field)
    {
        $foreignKeyList = $this->getSchema()->getForeignKeyArray($this->getName());
        foreach ($foreignKeyList as $foreignKey) {
            if ($foreignKey['column_name'] == $field) {
                return true;
            }
        }

        return false;
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

    /**
     * Check table as tree
     *
     * @return bool
     */
    public function isTree()
    {
        return $this->getColumn('parent_id') && $this->getColumn('tree_path') && $this->getColumn('level') && $this->getColumn('pos');
    }

    /**
     * Есть ли уникальный индес для поле (точнее входит ли поле в уникальные ключи PRIMARY, UNIQUE и в составные)
     *
     * @param $field
     * @return bool
     */
    public function hasUniqueIndexForField($field)
    {
        $indexList = $this->getIndex();

        foreach ($indexList as $index) {
            if ($index->isUnique() && $index->hasColumn($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param AbstractIndex $index
     * @return $this
     */
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

    /**
     * @param null $indexName
     * @return array|\Model\Cluster\Schema\Table\Index\AbstractIndex[]|\Model\Cluster\Schema\Table\Index\AbstractIndex
     */
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

    /**
     * @param AbstractLink $link
     * @return $this
     */
    public function addLink(AbstractLink $link)
    {
        $this->_linkList[$link->getName()] = $link;
        return $this;
    }

    /**
     * @param null $linkName
     * @return AbstractLink|AbstractLink[]|array
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

    /**
     * @return \Model\Db\Mysql
     */
    public function getDb()
    {
        return $this->getSchema()->getDb();
    }

    /**
     * @param Column $column
     * @return $this
     */
    public function addColumn(Column $column)
    {
        $this->_columnByNameRegistry[$column->getName()] = $column;
        $this[] = $column;

        return $this;
    }

    /**
     * Get column by name 
     * 
     * @param string $column
     * @return Column|Column[]
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

    /**
     * @param bool $deep
     * @return array
     */
    public function toArray($deep = true)
    {
        $result = array('name' => $this->getName());
        
        $result['columns'] = array();

        /** @var Table|Column[] $this */
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

    /**
     * @param string $column
     * @param string $table
     * @return bool|AbstractLink
     */
    public function getLinkByColumn($column, $table)
    {
        if ($column instanceof Column) {
            $column = $column->getName();
        }

        if ($table instanceof Table) {
            $table = $table->getName();
        }

        $column = (string)$column;
        $table  = (string)$table;

        foreach ($this->getLink() as $link) {
            if (($link->getLocalTable()->getName() == $table && $link->getLocalColumn()->getName() == $column) ||
                ($link->getForeignTable()->getName() == $table && $link->getForeignTable()->getName() == $column)) {
                return $link;
            }
        }

        return false;
    }

    /**
     * @return int|mixed
     */
    public function getMaxColumnNameLength()
    {
        $result = 0;
        /** @var Table|Column[] $this */
        foreach ($this as $column) {
            $result = max($result, strlen($column->getName()));
        }

        return $result;
    }

    public function toXml($withHeader = true, $tabStep = 3)
    {
        $tab = '    ';
        $shift = str_repeat($tab, $tabStep);

        $xml = $withHeader ? \Model\Cluster::XML_HEADER . "\n" : '';

        $xml .= $shift . '<table name="' . $this->getName() . '">' . "\n";

        $xml .= $shift . $tab . '<columns>' . "\n";
        /** @var $this Table|Column[]*/
        foreach ($this as $column) {
            $xml .= $column->toXml(false, $tabStep + 2);
        }
        $xml .= $shift . $tab . '</columns>' . "\n";

        $xml .= $shift . $tab . '<indexes>' . "\n";
        foreach ($this->getIndex() as $index) {
            $xml .= $index->toXml(false, $tabStep + 2);
        }
        $xml .= $shift . $tab . '</indexes>' . "\n";

        $xml .= $shift . $tab . '<links>' . "\n";
        foreach ($this->getLink() as $link) {
            $xml .= $link->toXml(false, $tabStep + 2);
        }
        $xml .= $shift . $tab . '</links>' . "\n";

        $xml .= $shift . '</table>' . "\n";

        return $xml;
    }
}