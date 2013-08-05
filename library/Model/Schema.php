<?php

namespace Model;

use Model\Schema\Table as Table;
use Model\Schema\Table\Column as Column;

use Model\Schema\Table\Link\OneToOne   as OneToOne;
use Model\Schema\Table\Link\OneToMany  as OneToMany;
use Model\Schema\Table\Link\ManyToOne  as ManyToOne;
use Model\Schema\Table\Link\ManyToMany as ManyToMany;

use Model\Schema\Table\Index\AbstractIndex as AbstractIndex;
use Model\Db\Mysql\Driver as DbAdapter;

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
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    protected $_db;
    
    /**
     * Database name
     * 
     * @var string
     */
    protected $_name;
    
    /**
     * Реестр имен таблиц по колонке 
     * @var type 
     */
    protected $_tableNameListByTableFieldNameRegistry = array();

    /**
     * Реестр таблиц по имени
     * @var type 
     */
    protected $_tableByTableNameRegistry = array();
    
    public function __construct($name, DbAdapter $dbAdapter)
    {
        if ($dbAdapter) {
            $this->_db = $dbAdapter;
        }
        
        $this->_name = preg_replace('#[^a-z0-9\_\-]#si', '', $name);
        
        $this->getDb()->query('USE ' . $this->getName());

        //$metadata = new \Zend\Db\Metadata\Metadata($dbAdapter);

        $sql = 'show tables';

        $tableNames = $dbAdapter->fetchCol($sql);

        $tables = array();
        if (!empty($tableNames)) {
            foreach ($tableNames as $tableName) {
                if ($tableName[0] == '_') {
                    continue;
                }
                
                $tables[] = new Table($tableName, $this);
            }
        }
        
        foreach ($tables as $table) {
            $this->_tableByTableNameRegistry[$table->getName()] = $table;
            
            foreach($table as $column) {
                $this->_tableNameListByTableFieldNameRegistry[$column->getName()][] = $table->getName(); 
            }
        }
        
        parent::__construct($tables);
        
        $this->initIndex();
        $this->initTableLinks();
    }
    
    public function initIndex()
    {
        $schemaName = $this->getName();
        
        // Инициализируем индексы для таблицы
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

        // Инициализируем связи на основе Foreign Key
        foreach ($this as $table) {
            $tableName = $table->getName();
            $foreignKeys = $this->getForeignKeyArray($tableName);

            if ($foreignKeys) {
                foreach ($foreignKeys as $foreignKey) {
                    $ruleUpdate = $foreignKey['update_rule'];
                    $ruleDelete = $foreignKey['delete_rule'];
                    
                    // Определяем связь One To One
                    $localTable   = $this->getTable($foreignKey['table_name']);
                    $localColumn  = $localTable->getColumn($foreignKey['column_name']);
                    
                    $foreignTable  = $this->getTable($foreignKey['referenced_table_name']);
                    $foreignColumn = $foreignTable->getColumn($foreignKey['referenced_column_name']);
                    
                    $link = $this->getLinkByColumns($foreignColumn, $foreignColumn, $ruleUpdate, $ruleDelete);
/*
                    // Если локальный связь прямая и локальный с внешним полем уникальты - то OneToOne
                    if ($localColumn->isUnique() 
                       && $foreignColumn->isUnique() 
                       && !$foreignTable->isLinkTable() && !$localTable->isLinkTable()) 
                    {
                        if ($localTable->getName() == $tableName) {
                            $link = new OneToOne($foreignKey['key_name'], $localColumn, $foreignColumn, $ruleDelete, $ruleUpdate);
                        } elseif ($foreignTable->getName() == $tableName) {
                            $link = new OneToOne($foreignKey['key_name'], $foreignColumn, $localColumn, $ruleDelete, $ruleUpdate);
                        }
                    } elseif ($localColumn->isUnique()
                              && !$foreignColumn->isUnique()
                              && $localTable->getName() == $tableName
                              && !$foreignTable->isLinkTable() && !$localTable->isLinkTable()) 
                    {  // OneToMany Direct
                        $link = new OneToMany($foreignKey['key_name'], $localColumn, $foreignColumn, $ruleDelete, $ruleUpdate);
                    } elseif (!$localColumn->isUnique()
                              && $foreignColumn->isUnique()
                              && $localTable->getName() == $tableName
                              && !$foreignTable->isLinkTable() && !$localTable->isLinkTable()) 
                    { // ManyToOne Direct
                        $link = new ManyToOne($foreignKey['key_name'], $localColumn, $foreignColumn, $ruleDelete, $ruleUpdate);
                    } elseif ($localColumn->isUnique()
                              && !$foreignColumn->isUnique()
                              && $foreignTable->getName() == $tableName
                              && !$foreignTable->isLinkTable() && !$localTable->isLinkTable()) 
                    {  // OneToMany Direct
                        $link = new ManyToOne($foreignKey['key_name'], $foreignColumn, $localColumn,  $ruleDelete, $ruleUpdate);
                    }  elseif (!$localColumn->isUnique()
                              && $foreignColumn->isUnique()
                              && $foreignTable->getName() == $tableName
                              && !$foreignTable->isLinkTable() && !$localTable->isLinkTable()) 
                    {  // OneToMany Direct
                        $link = new OneToMany($foreignKey['key_name'], $foreignColumn, $localColumn,  $ruleDelete, $ruleUpdate);
                    } elseif ($localTable->isLinkTable() || $foreignTable->isLinkTable()) {
                        $linkTable  = $localTable->isLinkTable() ? $localTable : $foreignTable;
                        
                        if ($localTable->isLinkTable()) {
                            $localTable = $foreignTable;
                            $localColumn = $foreignColumn;
                        }
                        
                        / **
                         * Тут мы выясняем сколько внешних ключей у таблицы связки
                         * Если их больше двух или меньше двух, то значит это хреновая связь
                         * 
                         * Потом мы выясняем внешнюю таблицку и создаем связь
                         * /
                        $linkTableForeignKeys = $this->getForeignKeyArray($linkTable);
                        
                        if (count($linkTableForeignKeys) == 2) {
                            foreach ($linkTableForeignKeys as $linkTableForeignKey) {
                                if ($linkTableForeignKey['table_name'] == $localTable->getName() || $linkTableForeignKey['referenced_table_name'] == $localTable->getName()) {
                                    $linkTableLocalColumnName = ($linkTableForeignKey['table_name'] == $localTable->getName()) ? $linkTableForeignKey['referenced_column_name'] : $linkTableForeignKey['column_name'];
                                    $linkTableLocalColumn = $this->getTable($linkTable->getName())->getColumn($linkTableLocalColumnName);
                                    continue;
                                } else {
                                    // Пытаемся найти внешнее поле foreignColumn
                                    if ($linkTableForeignKey['table_name'] == $linkTable->getName()) { 
                                        $foreignTableName = $linkTableForeignKey['referenced_table_name']; 
                                        $foreignColumnName = $linkTableForeignKey['referenced_column_name']; 
                                        $linkTableForeignColumnName = $linkTableForeignKey['column_name']; 
                                    } else {
                                        $foreignTableName = $linkTableForeignKey['table_name']; 
                                        $foreignColumnName = $linkTableForeignKey['column_name']; 
                                        $linkTableForeignColumnName = $linkTableForeignKey['referenced_column_name']; 
                                    }
                                    
                                    $linkTableForeignColumn = $this->getTable($linkTable->getName())->getColumn($linkTableForeignColumnName);
                                    $foreignColumn = $this->getTable($foreignTableName)->getColumn($foreignColumnName);
                                }
                            }
                            $link = new ManyToMany($foreignKey['key_name'], $localColumn, $foreignColumn, $ruleDelete, $ruleUpdate, $linkTableLocalColumn, $linkTableForeignColumn);
                        }
                    }
                    */
                    if ($link && !isset($tableLinkRegistry[$link->getLocalTable()->getName()][$link->getUniqId()])) {
                        $tableLinkRegistry[$link->getLocalTable()->getName()][$link->getUniqId()] = $link;
                        $link->getLocalTable()->addLink($link);
                    }

                    $inverse  = $link->inverse();

                    if ($inverse && !isset($tableLinkRegistry[$inverse->getLocalTable()->getName()][$inverse->getUniqId()])) {
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

                    if ($link) {
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
        }
    }


    protected function getLinkByColumns(Column $localColumn, Column $foreignColumn, $ruleUpdate = null, $ruleDelete = null)
    {
        $localTable   = $localColumn->getTable();
        $foreignTable = $foreignColumn->getTable();

        $link = null;

        // Если локальный связь прямая и локальный с внешним полем уникальты - то OneToOne
        if ($localColumn->isUnique()
           && $foreignColumn->isUnique()
           && !$foreignTable->isLinkTable() && !$localTable->isLinkTable())
        {
            if ($localColumn->getName() == 'id') {
                $name = $foreignColumn->getEntityNameAsCamelCase() . '___' . $foreignTable->getNameAsCamelCase();
            } else {
                $name = $localColumn->getEntityNameAsCamelCase() . '___' . $localTable->getNameAsCamelCase();
            }

            $link = new OneToOne('OneToOne___' . $name, $localColumn, $foreignColumn, $ruleDelete, $ruleUpdate);
        } elseif ($localColumn->isUnique()
                  && !$foreignColumn->isUnique()
                  && !$foreignTable->isLinkTable() && !$localTable->isLinkTable())
        {  // OneToMany Direct
            $link = new OneToMany('OneToMany___' . $foreignColumn->getEntityNameAsCamelCase() . '___' . $foreignTable->getNameAsCamelCase(), $localColumn, $foreignColumn, $ruleDelete, $ruleUpdate);
        } elseif (!$localColumn->isUnique()
                  && $foreignColumn->isUnique()
                  && !$foreignTable->isLinkTable() && !$localTable->isLinkTable())
        { // ManyToOne Direct
            $link = new ManyToOne('ManyToOne___' . $localTable->getNameAsCamelCase() . '___' . $localColumn->getEntityNameAsCamelCase(), $localColumn, $foreignColumn, $ruleDelete, $ruleUpdate);
        } elseif ($localTable->isLinkTable() || $foreignTable->isLinkTable()) {
            $linkTable  = $localTable->isLinkTable() ? $localTable : $foreignTable;

            if ($localTable->isLinkTable()) {
                $localTable = $foreignTable;
                $localColumn = $foreignColumn;
            }

            /**
             * Тут мы выясняем сколько внешних ключей у таблицы связки
             * Если их больше двух или меньше двух, то значит это хреновая связь
             *
             * Потом мы выясняем внешнюю таблицку и создаем связь
             */
            $linkTableForeignKeys = $this->getForeignKeyArray($linkTable);

            $linkTableColumnList    = $linkTable->getColumn();
            $linkTableLocalColumn   = null;
            $linkTableForeignColumn = null;
            $foreignColumn          = null;

            foreach ($linkTableColumnList as $linkTableColumn) {
                // НЕ ID поле
                if (substr($linkTableColumn->getName(), -3) != '_id') {
                    continue;
                }

                $tableName = substr($linkTableColumn->getName(), 0, -3);
                $matchedTable = $this->getTable($tableName);

                if ($matchedTable) {
                    if ($matchedTable->getName() == $localTable->getName()) {
                        $linkTableLocalColumn = $linkTableColumn;
                    } else {
                        $foreignColumn = $matchedTable->getColumn('id');
                        $linkTableForeignColumn = $linkTableColumn;
                    }
                }
            }

            if (!empty($linkTableForeignKeys)) {
                foreach ($linkTableForeignKeys as $linkTableForeignKey) {
                    if ($linkTableForeignKey['table_name'] == $localTable->getName() || $linkTableForeignKey['referenced_table_name'] == $localTable->getName()) {
                        $linkTableLocalColumnName = ($linkTableForeignKey['table_name'] == $localTable->getName()) ? $linkTableForeignKey['referenced_column_name'] : $linkTableForeignKey['column_name'];
                        $linkTableLocalColumn = $this->getTable($linkTable->getName())->getColumn($linkTableLocalColumnName);
                        continue;
                    } else {
                        // Пытаемся найти внешнее поле foreignColumn
                        if ($linkTableForeignKey['table_name'] == $linkTable->getName()) {
                            $foreignTableName = $linkTableForeignKey['referenced_table_name'];
                            $foreignColumnName = $linkTableForeignKey['referenced_column_name'];
                            $linkTableForeignColumnName = $linkTableForeignKey['column_name'];
                        } else {
                            $foreignTableName = $linkTableForeignKey['table_name'];
                            $foreignColumnName = $linkTableForeignKey['column_name'];
                            $linkTableForeignColumnName = $linkTableForeignKey['referenced_column_name'];
                        }

                        $linkTableForeignColumn = $this->getTable($linkTable->getName())->getColumn($linkTableForeignColumnName);
                        $foreignColumn = $this->getTable($foreignTableName)->getColumn($foreignColumnName);
                    }
                }

            }

            if ($foreignColumn && $linkTableLocalColumn && $linkTableForeignColumn) {
                $linkTableLocalColumnNameAsCamelCase = implode(array_map('ucfirst', explode('_', preg_replace('#_id$#', '', $linkTableLocalColumn->getName()))));
                $linkTableForeignColumnNameAsCamelCase = implode(array_map('ucfirst', explode('_', preg_replace('#_id$#', '', $linkTableForeignColumn->getName()))));
                $link = new ManyToMany('ManyToMany___' . $linkTableLocalColumnNameAsCamelCase . '___' . $linkTableForeignColumnNameAsCamelCase, $localColumn, $foreignColumn, $ruleDelete, $ruleUpdate, $linkTableLocalColumn, $linkTableForeignColumn);
            }
        }

        return $link;
    }
    
    
    protected function getForeignKeyArray($table)
    {
        $schemaName = $this->getName();

        $tableName = ($table instanceof Table) ? $table->getName() : (string)$table;
        
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

            return $this->getDb()->fetchAll($sql);
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
     * Set DB Adapter
     *
     * @param DbAdapter $dbAdapter
     * @return \Model\Schema
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
    
    public function toArray($deep = true)
    {
        $result = array('name' => $this->getName());
        
        $result['tables'] = array();
        
        foreach ($this as $table) {
            $result['tables'][] = $table->toArray($deep);
        }
        
        return $result;
    }
    
}