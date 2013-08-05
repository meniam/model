<?php

namespace ModelTest;

require_once __DIR__ . '/../TestCase.php';
use Model\Cluster\Schema;

abstract class TestCase extends \TestCase
{
    /**
     * @var \Model\Db\Mysql
     */
    private static $db;

    /**
     * @var Schema
     */
    protected static $_schema;

    public static function setDb($db)
    {
        self::$db = $db;
    }

    /**
     * @param bool $isRenew
     * @return Schema
     */
    protected function getSchema($isRenew = false)
    {
        if (self::$_schema instanceof Schema && !$isRenew) {
            return self::$_schema;
        }

        self::$_schema = new \Model\Cluster\Schema('model_test', $this->getDb());
        self::$_schema->init();

        return self::$_schema;
    }

    /**
     *
     * @return \Model\Db\Mysql
     */
    public function getDb()
    {
        return self::$db;
    }
}
