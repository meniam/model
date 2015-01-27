<?php

namespace Model;

use ArrayObject;
use \Model\Cluster\Schema;
use \Model\Cluster\Schema\Table;

class Cluster extends ArrayObject
{
    /**
     * Реестр таблиц по имени
     * @var array
     */
    protected $_tableByTableNameRegistry = array();

    /**
     * @param Cluster\Schema $schema
     * @return Cluster
     */
    public function addSchema(Schema $schema)
    {
        $this[$schema->getName()] = $schema;

        $tableList = $schema->getTableList();

        foreach ($tableList as $tableName => $table) {
            if (!isset($this->_tableByTableNameRegistry[$tableName])) {
                $this->_tableByTableNameRegistry[$tableName] = $table;
            }
        }
        return $this;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasSchema($name)
    {
        return $this->offsetExists($name);
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
     * @return array|Table[]
     */
    public function getTablelist()
    {
        return $this->_tableByTableNameRegistry;
    }
}