<?php

namespace Model;

use ArrayObject;
use \Model\Cluster\Schema;
use \Model\Cluster\Schema\Table;
use \Model\Cluster\Schema\Table\Column;

class Cluster extends ArrayObject
{
    const XML_HEADER = '<?xml version="1.0" encoding="UTF-8" ?>';

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

    /**
     * @param $xml
     * @param $db
     * @return Cluster
     */
    public static function fromXml($xml, $db)
    {
        if (is_array($xml)) {
            $data = $xml;
        } else {
            $xml = simplexml_load_string($xml);
            $data = json_decode(json_encode((array) $xml), 1);
        }

        $data = Column::prepareXmlArray($data);
        $schemaArrayList = is_int(key($data['schema'])) ? $data['schema'] : array($data['schema']);

        $cluster = new Cluster();
        foreach ($schemaArrayList as $schemaArray) {
            $schema = \Model\Cluster\Schema::fromXml($schemaArray, $db);
            $cluster->addSchema($schema);
        }

        return $cluster;
    }

    public function toXml($withHeader = true, $tabStep = 0)
    {
        $tab = '    ';
        $shift = str_repeat($tab, $tabStep);

        $xml = $withHeader ? \Model\Cluster::XML_HEADER . "\n" : '';


        $xml .= $shift . "<cluster>" . "\n";

        /** @var $this Cluster|Schema[] */
        foreach ($this as $schema) {
            $xml .= $schema->toXml(false, 1);
        }

        $xml .= $shift . "</cluster>\n";

        return $xml;
    }
}