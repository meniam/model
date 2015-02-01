<?php

namespace Model;

use Model\Config\Config;
use Model\Db\Mysql;
use Model\Exception\ErrorException;
use Model\Validator\Adapter\AdapterInterface;
use Model\Validator\Adapter\Zend;

/**
 * Base model class
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

    private static $validatorAdapter;

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

        $connectionCount = count($connections);
        foreach ($connections as $name => $connection) {
            $isDefault = $connectionCount == 1 ? true : $connection['default'];
            self::addDb(new Mysql($connection['dsn'], $connection['user'], $connection['password']), $name, $isDefault);
        }

        return self::$config;
    }

    /**
     * @param AdapterInterface $validatorAdapter
     */
    public static function setValidatorAdapter(AdapterInterface $validatorAdapter)
    {
        self::$validatorAdapter = $validatorAdapter;
    }

    /**
     * @return AdapterInterface
     */
    public static function getValidatorAdapter()
    {
        if (!self::$validatorAdapter) {
            self::setValidatorAdapter(new Zend());
        }

        return self::$validatorAdapter;
    }

    public static function initialize()
    {
        if (!self::isInit()) {
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
        $connectionName = $connectionName ? (string)$connectionName : 'db';

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