<?php

namespace Model;
use Model\Config\Config;
use Model\Db\Mysql;
use Model\Exception\ErrorException;

/**
 * Основной класс моделей
 *
 * @category   Model
 * @package    Model
 * @author     Eugene Myazin <meniam@gmail.com>
 * @version    SVN: $Id$
 */
abstract class Model
{
    /**
     * @var Config
     */
    private static $config;

    /**
     * @var Mysql
     */
    private static $defaultConnection;

    /**
     * @var Mysql[]
     */
    private static $connections = array();

    /**
     * @return bool
     */
    public static function isInit()
    {
        return (self::$config !== null);
    }

    /**
     * @param Config $configuration
     * @return array|Config
     */
    public static function setConfig(Config $configuration)
    {
        self::$config = $configuration;

        $connections = self::$config['connections'];

        foreach ($connections as $name => $connectionData) {
            if (!isset($connectionData['connection'])) {
                continue;
            }

            $connection = $connectionData['connection'];
            self::addDb(new Mysql($connection['dsn'], $connection['user'], $connection['password']), $name, $connection['default']);
        }

        return self::$config;
    }

    public static function initialize()
    {
        if (self::isInit()) {
            throw new ErrorException("Models cannot be initialized without a valid configuration");
        }
    }

    /**
     * @param Mysql $connection
     * @param null $connectionName
     * @param bool $isDefault
     * @throws ErrorException
     */
    public static function addDb(Mysql $connection, $connectionName = null, $isDefault = false)
    {
        $connectionName = $connectionName ? (string)$connectionName : $connection->getSchema();

        if (isset(self::$connections[$connectionName])) {
            throw new ErrorException("Connection with the same name already registered");
        }

        self::$connections[$connectionName] = $connection;

        if ($isDefault) {
            self::$defaultConnection = $connection;
        }
    }

    /**
     * @param string|null $connectionName
     * @return Mysql
     * @throws ErrorException
     */
    public static function getDb($connectionName = null)
    {
        if (!$connectionName) {
            return self::$defaultConnection;
        } elseif (isset(self::$connections[$connectionName])) {
            return self::$connections[$connectionName];
        }

        throw new ErrorException('Connection not defined');
    }

}