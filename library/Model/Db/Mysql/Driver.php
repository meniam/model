<?php

namespace Model\Db\Mysql;

/**
 * Драйвер MySQL базы данных
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Driver
{
    /**
     * @var \PDO
     */
    protected $resource;

    /**
     * @var array
     */
    protected $connectionParameters;

    private $connectTries = 0;

    private $lastExecutionTime;

    private $reconnectTimeout = 10;

    /**
     *
     * @param array|\PDO|null $connectionParameters
     * @throws \Model\Db\Exception\InvalidArgumentException
     */
    public function __construct($connectionParameters = null)
    {
        if (is_array($connectionParameters)) {
            $this->setConnectionParameters($connectionParameters);
        } elseif ($connectionParameters instanceof \PDO) {
            $this->setResource($connectionParameters);
        } elseif (null !== $connectionParameters) {
            throw new \Model\Db\Exception\InvalidArgumentException('$connection must be an array of parameters, a PDO object or null');
        }
    }

    /**
     * @param array $connectionParameters
     */
    public function setConnectionParameters(array $connectionParameters)
    {
        $this->connectionParameters = $connectionParameters;
        if (isset($connectionParameters['dsn'])) {
            $this->driverName = substr($connectionParameters['dsn'], 0,
                strpos($connectionParameters['dsn'], ':')
            );
        } elseif (isset($connectionParameters['pdodriver'])) {
            $this->driverName = strtolower($connectionParameters['pdodriver']);
        } elseif (isset($connectionParameters['driver'])) {
            $this->driverName = strtolower(substr(
                str_replace(array('-', '_', ' '), '', $connectionParameters['driver']),
                3
            ));
        }
    }

    /**
     * @return array
     */
    public function getConnectionParameters()
    {
        return $this->connectionParameters;
    }

    /**
     * Set resource
     *
     * @param  \PDO $resource
     * @return Mysql
     */
    public function setResource(\PDO $resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return \PDO
     */
    public function getResource()
    {
        return $this->resource;
    }

    public function connect()
    {
        $dsn = $username = $password = $hostname = $database = null;
        $options = array();
        foreach ($this->connectionParameters as $key => $value) {
            switch (strtolower($key)) {
                case 'dsn':
                    $dsn = $value;
                    break;
                case 'driver':
                    $value = strtolower($value);
                    if (strpos($value, 'pdo') === 0) {
                        $pdoDriver = strtolower(substr(str_replace(array('-', '_', ' '), '', $value), 3));
                    }
                    break;
                case 'pdodriver':
                    $pdoDriver = (string) $value;
                    break;
                case 'user':
                case 'username':
                    $username = (string) $value;
                    break;
                case 'pass':
                case 'password':
                    $password = (string) $value;
                    break;
                case 'host':
                case 'hostname':
                    $hostname = (string) $value;
                    break;
                case 'database':
                case 'dbname':
                    $database = (string) $value;
                    break;
                case 'driver_options':
                case 'options':
                    $value = (array) $value;
                    $options = array_diff_key($options, $value) + $value;
                    break;
                default:
                    $options[$key] = $value;
                    break;
            }
        }

        if (!isset($dsn) && isset($pdoDriver)) {
            $dsn = array();

            if (isset($database)) {
                $dsn[] = "dbname={$database}";
            }
            if (isset($hostname)) {
                $dsn[] = "host={$hostname}";
            }

            $dsn = $pdoDriver . ':' . implode(';', $dsn);
        } elseif (!isset($dsn)) {
            throw new \Model\Db\Exception\ErrorException(
                'A dsn was not provided or could not be constructed from your parameters',
                $this->connectionParameters
            );
        }

        try {
            if ($this->connectTries < 10) {
                $this->resource = new \PDO($dsn, $username, $password, $options);
                $this->resource->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $this->resource->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
                $this->connectTries = 0;
            }
        } catch (\PDOException $e) {
            $this->connectTries++;
            throw new \Model\Db\Exception\ErrorException('Connect Error: ' . $e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        if (!($this->resource instanceof \PDO)) {
            return false;
        }

        if ($this->lastExecutionTime && (time() - $this->lastExecutionTime) > $this->reconnectTimeout) {
            try {
                $this->resource->query('SELECT 1');
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Mysql
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->resource = null;
        }

        return $this;
    }

    /**
     * @param string $sql
     * @return array
     */
    public function fetchRow($sql)
    {
        $stmt = $this->query($sql);

        if (!($result = $stmt->fetch(\PDO::FETCH_ASSOC))) {
            return array();
        }

        return $result;
    }

    /**
     *
     * @param string $sql
     * @param null   $entityType
     * @return array
     */
    public function fetchAll($sql, $entityType = null)
    {
        $stmt = $this->query($sql);

        if ($entityType) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $entityType);
        }

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchCol($sql, $col = 0)
    {
        $stmt = $this->query($sql);

        $data = $stmt->fetchAll(\PDO::FETCH_BOTH);

        $result = array();
        foreach ($data as $k => $v) {
            if (isset($v[$col])) {
                $result[] = $v[$col];
            }
        }

        return $result;
    }

    /**
     * @param string $sql
     * @return array|null
     */
    public function fetchPairs($sql)
    {
        $stmt = $this->query($sql);

        if (!($result = $stmt->fetch(\PDO::FETCH_KEY_PAIR))) {
            return array();
        }

        return $result;
    }

    /**
     * @param string $sql
     * @return mixed
     */
    public function fetchOne($sql)
    {
        $stmt = $this->query($sql);

        return $stmt->fetchColumn(0);
    }

    /**
     * @param $sql
     * @return \PDOStatement
     */
    public function query($sql)
    {
        $stmt = $this->prepareStatement($sql);

        // call the stored procedure
        $stmt->execute();

        $this->lastExecutionTime = time();

        return $stmt;
    }

    /**
     * @param $sql
     * @return \PDOStatement
     */
    protected function prepareStatement($sql)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $stmt = $this->getResource()->prepare($sql);

        return $stmt;
    }
}
