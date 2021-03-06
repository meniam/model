<?php

namespace Model;

use Model\Config\Config;
use Model\Db\Mysql;
use Model\Exception\ErrorException;
use Model\Validator\Adapter\AbstractAdapter;
use Model\Validator\Adapter\WithoutValidation;

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
            $params = isset($connection['params']) ? $connection['params'] : array();
            $isDefault = $connectionCount == 1 ? true : $connection['default'];
            self::addDb(new Mysql($connection['dsn'], $connection['user'], $connection['password'], $params), $name, $isDefault);
        }

        $validationAdapter = !empty(self::$config['validator_adapter']) ? self::$config['validator_adapter'] : '\\Model\Validator\Adapter\\WithoutValidation';
        self::initializeValidatorAdapter($validationAdapter);

        return self::$config;
    }

    /**
     * @param string $adapterClass
     *
     * @return object
     * @throws ErrorException
     */
    protected static function initializeValidatorAdapter($adapterClass)
    {
        $_validatorAdapter = new \ReflectionClass($adapterClass);

        if (!$_validatorAdapter->isSubclassOf('\Model\Validator\Adapter\AbstractAdapter')) {
            throw new ErrorException('Validator adapter must be instance of Model\Validator\Adapter\AbstractAdapter');
        }

        self::setValidatorAdapter($_validatorAdapter->newInstance());
    }

    /**
     * @param AbstractAdapter $validatorAdapter
     */
    public static function setValidatorAdapter(AbstractAdapter $validatorAdapter)
    {
        self::$validatorAdapter = $validatorAdapter;
    }

    /**
     * @return AbstractAdapter
     */
    public static function getValidatorAdapter()
    {
        if (!self::$validatorAdapter) {
            self::setValidatorAdapter(new WithoutValidation());
        }

        return self::$validatorAdapter;
    }

    public static function initialize()
    {
        if (!self::isInit()) {
            throw new ErrorException("Models cannot be initialized without a valid configuration");
        }
        return true;
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