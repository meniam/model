<?php

namespace Model\Cluster;

use Model\Cluster\Schema\Table as Table;
use Model\Cluster\Schema\Table\Column as Column;

use Model\Cluster\Schema\Table\Link\OneToOne   as OneToOne;
use Model\Cluster\Schema\Table\Link\OneToMany  as OneToMany;
use Model\Cluster\Schema\Table\Link\ManyToOne  as ManyToOne;
use Model\Cluster\Schema\Table\Link\ManyToMany as ManyToMany;

use Model\Cluster\Schema\Table\Index\AbstractIndex;
use Model\Cluster\Schema\Table\Link\AbstractLink;
use Model\Db\Mysql as DbAdapter;

/**
 * Схема базы данных
 *   - содержит внутри перечень таблиц
 * 
 * @author Eugene Myazin <meniam@gmail.com>
 */
class Schema extends \ArrayIterator
{
    /**
     * DB Adapter
     * @var DbAdapter
     */
    protected $_db;
    
    /**
     * Database name
     * 
     * @var string
     */
    protected $_name;

    /**
     * @var \Zend\Cache\Storage\Adapter\Filesystem
     */
    protected static $cacheAdapter;

    /**
     * @var string
     */
    private $dbAdapterName = 'db';

    /**
     * Реестр имен таблиц по колонке 
     * @var array
     */
    protected $_tableNameListByTableFieldNameRegistry = array();

    /**
     * Реестр таблиц по имени
     * @var array
     */
    protected $_tableByTableNameRegistry = array();
    
    public function __construct($name, DbAdapter $dbAdapter, $dbAdapterName = 'db')
    {
        if ($dbAdapter) {
            $this->_db = $dbAdapter;
        }
        
        $this->_name = preg_replace('#[^a-z0-9\_\-]#si', '', $name);

        $this->setDbAdapterName($dbAdapterName);
    }

    /**
     * @param string $dbAdapterName
     */
    public function setDbAdapterName($dbAdapterName)
    {
        $this->dbAdapterName = (string)$dbAdapterName;
    }

    /**
     * @return string
     */
    public function getDbAdapterName()
    {
        return $this->dbAdapterName;
    }

    /**
     * @return Schema
     */
    public function init()
    {
        $this->getDb()->query('USE ' . $this->getName());

        $sql = 'show tables';

        $tableNames = array();
        $_tableNames = $this->_db->fetchAll($sql);

        uasort($_tableNames, function ($a, $b) {
            $v = reset($a);
            $vv = reset($b);

            if (substr($v, -5) == '_link' && substr($vv, -5) == '_link') {
                if (substr_count($v, '_') == substr_count($vv, '_')) {
                    return strcmp($v, $vv);
                } else {
                    return substr_count($v, '_') > substr_count($vv, '_');
                }
            } elseif (substr($v, -5) == '_link') {
                return true;
            } else {
                if (substr_count($v, '_') == substr_count($vv, '_')) {
                    return strcmp($v, $vv);
                } else {
                    return substr_count($v, '_') > substr_count($vv, '_');
                }
            }
        });


        foreach ($_tableNames as $name) {
            $tableNames[] = reset($name);
        }

        if (!empty($tableNames)) {
            foreach ($tableNames as $tableName) {
                if ($tableName[0] == '_') {
                    continue;
                }

                $table = new Table($tableName, $this);
                $this->addTable($table->init());
            }
        }

        $this->initIndex();

        /** @var Schema|Table[] $this */
        foreach ($this as $table) {
            /** @var Table|Column[] $table */
            foreach ($table as $column) {
                $column->init();
            }
        }

        $this->initTableLinks();

        return $this;
    }

    /**
     * @param Schema\Table $table
     * @return Schema
     */
    public function addTable(Table $table)
    {
        $this->_tableByTableNameRegistry[$table->getName()] = $table;

        /** @var $table Table|Column[] */
        foreach($table as $column) {
            $this->_tableNameListByTableFieldNameRegistry[$column->getName()][] = $table->getName();
        }

        $this[] = $table;
        return $this;
    }

    /**
     * @param           $xml
     * @param DbAdapter $db
     * @return Schema
     */
    public static function fromXml($xml, DbAdapter $db)
    {
        if (is_array($xml)) {
            $data = $xml;
        } else {
            $xml = simplexml_load_string($xml);
            $data = json_decode(json_encode((array) $xml), 1);
        }
        $data = Column::prepareXmlArray($data);

        $name = $data['@attributes']['name'];

        $schema = new Schema($name, $db);

        foreach ($data['tables']['table'] as $tableArray) {
            $table = Table::fromXml($tableArray, $schema);
            $schema->addTable($table);
        }

        foreach ($data['tables']['table'] as $tableArray) {
            $indexArrayList = is_int(key($tableArray['indexes']['index'])) ? $tableArray['indexes']['index'] : $tableArray['indexes'];
            $table = $schema->getTable($tableArray['@attributes']['name']);

            foreach ($indexArrayList as $indexArray) {
                $index = AbstractIndex::fromXml($indexArray, $table);
                $table->addIndex($index);
            }

            if (isset($tableArray['links']['link'])) {
                $linkArrayList = is_int(key($tableArray['links']['link'])) ? $tableArray['links']['link'] : $tableArray['links'];

                foreach ($linkArrayList as $linkArray) {
                    $link = AbstractLink::fromXml($linkArray, $table);
                    $table->addLink($link);
                }
            }
        }

        return $schema;
    }

    /**
     * @throws \Model\Exception\ErrorException
     */
    public function initIndex()
    {
        $schemaName = $this->getName();
        
        // Инициализируем индексы для таблицы
        /** @var Schema|Table[] $this */
        foreach ($this as $table) {
            $tableName = $table->getName();
            
            $sql = "SELECT *
                    FROM `information_schema`.`STATISTICS`
                    WHERE `TABLE_SCHEMA` = '{$schemaName}'
                        AND `TABLE_NAME` = '{$tableName}'
                    ORDER BY `SEQ_IN_INDEX`";

            $tableIndexes =  $this->getDb()->fetchAll($sql);

            $indexRegistry = array();
            $indexColumnRegistry = array();
            if ($tableIndexes) {
                foreach ($tableIndexes as $tableIndex) {
                    $indexRegistry[$tableIndex['INDEX_NAME']] = array('index_column' => $tableIndex, 'column' => $table->getColumn($tableIndex['COLUMN_NAME'])) ;
                    $_index = $table->getColumn($tableIndex['COLUMN_NAME']);
                    if (!$_index) {
                        throw new \Model\Exception\ErrorException('Index not found ' . $table->getName() . '.' . $tableIndex['COLUMN_NAME']);
                    }
                    $indexColumnRegistry[$tableIndex['INDEX_NAME']][] = $table->getColumn($tableIndex['COLUMN_NAME']);
                }
            }

            foreach ($indexRegistry as $indexName => $data) {
                if ($indexName == 'PRIMARY') {
                    $indexType = AbstractIndex::TYPE_PRIMARY;
                } elseif ($data['index_column']['NON_UNIQUE'] == 0) {
                    $indexType = AbstractIndex::TYPE_UNIQUE;
                } else {
                    $indexType = AbstractIndex::TYPE_KEY;
                }


                $index = new $indexType($indexName, $indexColumnRegistry[$indexName]);
                $table->addIndex($index);
            }
        }
    }
    
    /**
     * Инициализируем связи
     * 
     * 
     */
    public function initTableLinks()
    {
        $tableLinkRegistry = array();

        $usedForeignNameRegistry = array();

        // Инициализируем связи на основе Foreign Key
        /** @var Schema|Table[] $this */
        foreach ($this as $table) {
            $tableName = $table->getName();
            $foreignKeys = $this->getForeignKeyArray($tableName);

            if ($foreignKeys) {
                foreach ($foreignKeys as $foreignKey) {
                    if (isset($usedForeignNameRegistry[$foreignKey['key_name']])) {
                        continue;
                    }

                    $usedForeignNameRegistry[$foreignKey['key_name']] = true;

                    $ruleUpdate = $foreignKey['update_rule'];
                    $ruleDelete = $foreignKey['delete_rule'];
                    
                    // Определяем связь One To One
                    $localTable   = $this->getTable($foreignKey['table_name']);
                    $localColumn  = $localTable->getColumn($foreignKey['column_name']);
                    
                    $foreignTable  = $this->getTable($foreignKey['referenced_table_name']);
                    $foreignColumn = $foreignTable->getColumn($foreignKey['referenced_column_name']);

                    if ($localColumn->getName() == $foreignColumn->getName() && $localTable->getName() == $foreignTable->getName()) {
                        continue;
                    } else {
                        $link = $this->getLinkByColumns($localColumn, $foreignColumn, $ruleUpdate, $ruleDelete);
                    }

                    if ($link && !isset($tableLinkRegistry[$link->getLocalTable()->getName()][$link->getUniqId()])) {
                        $tableLinkRegistry[$link->getLocalTable()->getName()][$link->getUniqId()] = $link;
                        $link->getLocalTable()->addLink($link);
                    }

                    if ($link) {
                        // Получаем обратный тип связи
                        $inverse  = $link->inverse();
                    } else {
                        $inverse = null;
                    }

                    if ($link && $inverse && !isset($tableLinkRegistry[$inverse->getLocalTable()->getName()][$inverse->getUniqId()])) {
                        $tableLinkRegistry[$inverse->getLocalTable()->getName()][$inverse->getUniqId()] = $inverse;
                        $inverse->getLocalTable()->addLink($inverse);
                    }
                }
            }

            if (isset($this->_tableNameListByTableFieldNameRegistry[$tableName . '_id'])) {
                $localTable   = $this->getTable($tableName);
                $localColumn  = $localTable->getColumn('id');

                foreach ($this->_tableNameListByTableFieldNameRegistry[$tableName . '_id'] as $_table) {
                    $foreignTable  = $this->getTable($_table);
                    $foreignColumn = $foreignTable->getColumn($tableName . '_id');

                    $link = $this->getLinkByColumns($localColumn, $foreignColumn);

                    if (isset($link)) {
                        if (!isset($tableLinkRegistry[$link->getLocalTable()->getName()][$link->getUniqId()])) {
                            $tableLinkRegistry[$link->getLocalTable()->getName()][$link->getUniqId()] = $link;
                            $link->getLocalTable()->addLink($link);
                        }

                        $inverse  = $link->inverse();

                        if (!isset($tableLinkRegistry[$inverse->getLocalTable()->getName()][$inverse->getUniqId()])) {
                            $tableLinkRegistry[$inverse->getLocalTable()->getName()][$inverse->getUniqId()] = $inverse;
                            $inverse->getLocalTable()->addLink($inverse);
                        }
                    }
                }
            }

            if ($table->getColumn('parent_id')) {
                $link = $this->getLinkByColumns($table->getColumn('id'), $table->getColumn('parent_id'));

                if (isset($link)) {
                    if (!isset($tableLinkRegistry[$link->getLocalTable()->getName()][$link->getUniqId()])) {
                        $tableLinkRegistry[$link->getLocalTable()->getName()][$link->getUniqId()] = $link;
                        $link->getLocalTable()->addLink($link);
                    }
                }
            }
        }
    }

    /**
     * @param Column $localColumn
     * @param Column $foreignColumn
     * @param null   $ruleUpdate
     * @param null   $ruleDelete
     * @return ManyToOne|OneToMany|OneToOne|null
     */
    protected function getLinkByColumns(Column $localColumn, Column $foreignColumn, $ruleUpdate = null, $ruleDelete = null)
    {
        return \Model\Cluster\Schema\Table\Link\AbstractLink::factory($localColumn, $foreignColumn, $ruleUpdate, $ruleDelete);
    }

    /**
     * @param Table|string $table
     * @return array
     */
    public function getForeignKeyArray($table)
    {
        $schemaName = $this->getName();

        $tableName = ($table instanceof Table) ? $table->getName() : (string)$table;
        $cacheId = 'generator_schema_getForeignKeyArray_' . $tableName;
        $cacheAdapter = $this->getCacheAdapter();

        $result = $cacheAdapter ? $cacheAdapter->getItem($cacheId) : null;

        if (!$cacheAdapter || is_null($result)) {
            $sql = "SELECT k.CONSTRAINT_NAME as key_name,
                           k.TABLE_SCHEMA as table_schema,
                           k.TABLE_NAME as table_name,
                           k.COLUMN_NAME as column_name,
                           k.REFERENCED_TABLE_SCHEMA as referenced_table_schema,
                           k.REFERENCED_TABLE_NAME as referenced_table_name,
                           k.REFERENCED_COLUMN_NAME as referenced_column_name,
                           r.UPDATE_RULE as update_rule,
                           r.DELETE_RULE as delete_rule
                    FROM `information_schema`.`KEY_COLUMN_USAGE` as k
                    JOIN `information_schema`.TABLE_CONSTRAINTS as c ON
                        k.`CONSTRAINT_SCHEMA` = c.`CONSTRAINT_SCHEMA`
                        AND k.`TABLE_NAME` = c.`TABLE_NAME`
                        AND k.`CONSTRAINT_NAME` = c.`CONSTRAINT_NAME`
                    JOIN `information_schema`.`REFERENTIAL_CONSTRAINTS` as r
                        ON (k.`CONSTRAINT_SCHEMA` = r.`CONSTRAINT_SCHEMA`
                            AND k.`TABLE_NAME` = r.`TABLE_NAME`
                            AND k.`CONSTRAINT_NAME` = r.`CONSTRAINT_NAME`)
                    WHERE ((k.`CONSTRAINT_SCHEMA` = '{$schemaName}'
                        AND k.`TABLE_NAME` = '{$tableName}')
                        OR  (k.`REFERENCED_TABLE_SCHEMA` = '{$schemaName}'
                            AND k.`REFERENCED_TABLE_NAME` = '{$tableName}'))
                        AND c.CONSTRAINT_TYPE = 'FOREIGN KEY'
                    ORDER BY k.ORDINAL_POSITION";

            $result = $this->getDb()->fetchAll($sql);

            if ($cacheAdapter) {
                $cacheAdapter->setItem($cacheId, $result);
            }
        }

        return $result;
    }
    
    /**
     * Get database name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Проверить наличие таблицы в базе
     *
     * @param string $table имя таблицы
     * @return bool
     */
    public function hasTable($table)
    {
        if ($table instanceof Table) {
            $table = $table->getName();
        }
        
        return isset($this->_tableByTableNameRegistry[$table]);
    }

    /**
     * Получить объект таблицы по имени
     *
     * @param string $table имя таблицы
     * @return Table
     */
    public function getTable($table)
    {
        if ($table instanceof Table) {
            $table = $table->getName();
        }
        
        if (isset($this->_tableByTableNameRegistry[$table])) {
            return $this->_tableByTableNameRegistry[$table];
        }

        return null;
    }

    /**
     * Получить все таблицы
     *
     * @return array
     */
    public function getTableList()
    {
        return $this->_tableByTableNameRegistry;
    }

    /**
     * Set DB Adapter
     *
     * @param DbAdapter $dbAdapter
     * @return \Model\Cluster\Schema
     */
    public function setDb(DbAdapter $dbAdapter)
    {
        $this->_db = $dbAdapter;
        return $this;
    }
    
    /**
     * Get DB Adapter
     *
     * @return DbAdapter
     */
    public function getDb()
    {
        return $this->_db;
    }

    /**
     * Вернуть данные в виде массива
     *
     * @param bool $deep
     * @return array
     */
    public function toArray($deep = true)
    {
        $result = array('name' => $this->getName());
        
        $result['tables'] = array();

        /** @var Schema|Table[] $this */
        foreach ($this as $table) {
            $result['tables'][] = $table->toArray($deep);
        }
        
        return $result;
    }

    /**
     * @param  $cacheAdapter
     */
    public static function setCacheAdapter($cacheAdapter)
    {
        self::$cacheAdapter = $cacheAdapter;
    }

    /**
     * @return \Zend\Cache\Storage\Adapter\Filesystem
     */
    public static function getCacheAdapter()
    {
        return self::$cacheAdapter;
    }

    /**
     * @param bool $withHeader
     * @param int  $tabStep
     * @return string
     */
    public function toXml($withHeader = true, $tabStep = 0)
    {
        $tab = '    ';
        $shift = str_repeat($tab, $tabStep);

        $xml = $withHeader ? \Model\Cluster::XML_HEADER : '';

        $xml .= $shift . '<schema name="' . $this->_name . '">' . "\n";
        $xml .= $shift . $tab . '<tables>' . "\n";
        /** @var $this Table[] */
        foreach ($this as $table) {
            $xml .= $table->toXml(false, $tabStep + 2);
        }

        $xml .= $shift . $tab . '</tables>' . "\n";

        $xml .= $shift . '</schema>' . "\n";
        return $xml;
    }
}