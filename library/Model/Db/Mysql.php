<?php

namespace Model\Db;

use Model\Db\Exception\ErrorException;
use \PDO;

/**
 * Работа с базой данных
 *
 * @category   category
 * @package    package
 * @author     Eugene Myazin <meniam@gmail.com>
 * @since      25.11.12 15:34
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Mysql
{
    /**
     * Use the INT_TYPE, BIGINT_TYPE, and FLOAT_TYPE with the quote() method.
     */
    const INT_TYPE    = 0;
    const BIGINT_TYPE = 1;
    const FLOAT_TYPE  = 2;

    private $isConnected = false;

    private $dsn = 'dblib:host=your_hostname;dbname=your_db;charset=UTF-8';

    private $user;

    private $password;

    private $params = array();

    private $profiler = array();

    private $profilerEnable = true;

    /**
     * @var PDO
     */
    private $pdo;

    /**
     * Keys are UPPERCASE SQL datatypes or the constants
     * Zend_Db::INT_TYPE, Zend_Db::BIGINT_TYPE, or Zend_Db::FLOAT_TYPE.
     *
     * Values are:
     * 0 = 32-bit integer
     * 1 = 64-bit integer
     * 2 = float or decimal
     *
     * @var array Associative array of datatypes to values 0, 1, or 2.
     */
    protected $_numericDataTypes = array(
        self::INT_TYPE    => self::INT_TYPE,
        self::BIGINT_TYPE => self::BIGINT_TYPE,
        self::FLOAT_TYPE  => self::FLOAT_TYPE
    );


    public function __construct($dsn, $user, $password, array $params = array())
    {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;
        $this->params = $params;
    }

    public function connect()
    {
        if (!$this->isConnected) {
            $this->isConnected = true;
        } else {
            return $this;
        }

        $defaultParams = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        );

        foreach ($this->params as $k => $v) {
            $defaultParams[$k] = $v;
        }

        $this->pdo = new \PDO($this->dsn, $this->user, $this->password, $defaultParams);
        return $this;
    }

    public function disconnect()
    {
        unset($this->pdo);
        $this->isConnected = false;
    }

    /**
     * @param bool $status
     * @return Mysql
     */
    public function setEnableProfiler($status = true)
    {
        $this->profilerEnable = $status;
        return $this;
    }

    /**
     * @return array
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
    }

    /**
     * @param       $sql
     * @param array $bindParams
     * @return \PDOStatement
     */
    private function execute($sql, array $bindParams = null)
    {
        $this->connect();

        $start = 0;
        if ($this->profilerEnable) {
            $start = microtime(true);
        }

        $stmt = $this->pdo->prepare($sql);
        $bindParams = $this->prepareBindParams($bindParams);

        $bindParams ? $stmt->execute($bindParams) : $stmt->execute();

        if ($this->profilerEnable) {
            $this->profiler[] = array(
                'query' => $this->buildSql($sql, $bindParams),
                'runtime' => (microtime(true) - $start));
        }

        return $stmt;
    }

    public function buildSql($sql, array $bindParams = null)
    {
        if (!$bindParams) {
            return $sql;
        }

        $bindParams = $this->prepareBindParams($bindParams);
        //$sql = str_replace(array_keys($bindParams), array_values($bindParams), $sql);
        $sql = strtr($sql, $bindParams);

        return $sql;
    }

    public function prepareBindParams(array $bindParams = null, $addColon = false)
    {
        $this->connect();

        if (!$bindParams) {
            return null;
        }

        foreach ($bindParams as $k => &$paramValue) {
            switch (\getType($paramValue)) {
                case 'array':
                    $paramValue = implode(',', array_map(array($this, '_quote'), $paramValue));
                    break;
                case 'int':
                    $paramValue = (int)$paramValue;
                    break;
                case 'boolean':
                    $paramValue = ((bool)$paramValue ? 1 : 0);
                    break;
                default:
                    $paramValue = (string)$paramValue;
                    break;
            }

            if ($addColon && $k[0] != ':') {
                $bindParams[':' . $k] = $paramValue;
                unset($bindParams[$k]);
            }
        }

        return $bindParams;
    }

    /**
     * @param       $sql
     * @param array $bindParams
     * @return \PDOStatement
     */
    public function query($sql, array $bindParams = array())
    {
        return $this->execute($sql, $bindParams);
    }

    public function fetchOne($sql, array $bindParams = array())
    {
        $stmt = $this->execute($sql, $bindParams);
        return $stmt->fetchColumn(0);
    }

    public function fetchCol($sql, array $bindParams = array(), $column = 0)
    {
        $stmt = $this->execute($sql, $bindParams);
        return $stmt->fetchColumn((int)$column);
    }

    public function fetchRow($sql, array $bindParams = array())
    {
        $stmt = $this->execute($sql, $bindParams);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchPairs($sql, array $bindParams = array())
    {
        $stmt = $this->execute($sql, $bindParams);

        $_result = $stmt->fetch(PDO::FETCH_NUM);

        $result = array();
        if ($_result) {
            foreach($_result as $res) {
                $result[$res[0]] = $res[1];
            }
        }

        return $result;
    }

    public function fetchAll($sql, array $bindParams = array())
    {
        $stmt = $this->execute($sql, $bindParams);
        return $stmt->fetchAll();
    }

    public function prepareInsert($table, array $data = array())
    {
        if (!$data) {
            return null;
        }

        $sql = "INSERT INTO `" . $table . '` SET ';

        foreach (array_keys($data) as $k) {
            $sql .= '`' . $k . '` = :' . $k . ', ';
        }

        $sql = rtrim($sql, ", ");
        return $this->pdo->prepare($sql);
    }

    /**
     * @param       $table
     * @param array $bind
     * @internal param bool $returnLastInsertId
     * @return bool
     */
    public function insert($table, array $bind = array())
    {
        $this->connect();

        /** @var $stmt \PDOStatement */
        $stmt = $this->prepareInsert($table, $bind);
        if ($stmt) {
            if (!empty($bind)) {
                foreach ($bind as $paramName => $paramValue) {
                    if ($paramValue === null) {
                        $stmt->bindValue($paramName, $paramValue, PDO::PARAM_INT);
                        unset($bind[$paramName]);
                    }
                }
            }

            $bind = $this->prepareBindParams($bind);

            if (!empty($bind)) {
                foreach ($bind as $paramName => $paramValue) {
                    $stmt->bindValue($paramName, $paramValue, PDO::PARAM_STR);
                }
            }

            $result = $stmt->execute();

            if ($result) {
                return isset($bind['id']) ? $bind['id'] : $this->pdo->lastInsertId();
            } else {
                return $result;
            }
        } else {
            return false;
        }
    }

    /**
     * Изменить данные в таблице по условию
     *
     * @param       $table
     * @param array $data
     * @param       $cond
     * @param null  $bind
     * @return bool|null
     */
    public function update($table, array $data, $cond, $bind = null)
    {
        $this->connect();

        if (!$data) {
            return null;
        }

        $sql = "UPDATE `" . $table . '` SET ';

        foreach ($data as $k => &$null) {
            $sql .= '`' . $k . '` = :' . $k . ', ';
        }

        $where  = $this->prepareWhere($cond, $bind);
        $bind = $data;

        $sql = rtrim($sql, ", ");
        $sql .= " WHERE " . $where;

        /** @var $stmt \PDOStatement */
        $stmt = $this->pdo->prepare($sql);

        if (!empty($bind)) {
            foreach ($bind as $paramName => $paramValue) {
                if ($paramValue === null) {
                    $stmt->bindValue($paramName, $paramValue, PDO::PARAM_INT);
                    unset($bind[$paramName]);
                }
            }
        }

        $bind = $this->prepareBindParams($bind);

        if (!empty($bind)) {
            foreach ($bind as $paramName => $paramValue) {
                $stmt->bindValue($paramName, $paramValue, PDO::PARAM_STR);
            }
        }

        return $stmt->execute();
    }

    /**
     * Удалить данные из таблицы по условию
     *
     * @param      $table
     * @param      $cond
     * @param null $bind
     * @return \PDOStatement
     * @throws ErrorException
     */
    public function delete($table, $cond, $bind = null)
    {
        $this->connect();

        $where  = $this->prepareWhere($cond, $bind);

        if (!$where) {
            throw new ErrorException('Where cannot be an empty');
        }

        $sql = "DELETE FROM `" . $table . '`';
        $sql .= " WHERE " . $where;

        $stmt = $this->pdo->prepare($sql);

        /** @var $stmt \PDOStatement */
        $stmt->execute($bind);

        return $stmt;
    }

    /**
     * @param      $cond
     * @param null $bind
     * @return string
     * @throws \Model\Exception\ErrorException
     */
    public function prepareWhere($cond, $bind = null)
    {
        $where = array();

        if ($cond instanceof  \Model\Db\Select) {
            $where = $cond->getPart(\Model\Db\Select::WHERE);

            if (!$bind) {
                $bind  = $cond->getBind();
            }

        } elseif (is_array($cond) && !empty($cond)) {
            $_bind = array();
            foreach ($cond as $k => $v) {
                if (is_array($v) && count($v) == 1) {
                    $v = reset($v);
                }

                if (is_null($v)) {
                    $where[] = '(' . $k . ' IS NULL)';
                } elseif (is_array($v)) {
                    $v = implode(',', array_map(array($this, '_quote'), $v));
                    $where[] = '(' . $k . " IN ($v))";
                } elseif ($v instanceof Expr) {
                    if (is_string($k)) {
                        $where[] = '(' . $k . " = {$v})";
                    } else {
                        $where[] = "(:{$v})";
                    }
                } elseif (is_int($v)) {
                    $where[] = '(' . $k . " = {$v})";
                } else {
                    $v = (string)$v;
                    if ($v[0] != ':') {
                        $v = $this->_quote((string)$v);
                    }
                    $where[] = '(' . $k . " = {$v})";
                }
            }

            if (!$bind) {
                $bind = $_bind;
            }
        } elseif (is_scalar($cond) || $cond instanceof Expr) {
            $where[] = (string)$cond;
        } else {
            throw new \Model\Exception\ErrorException('Wrong where');
        }

        $bind = $this->prepareBindParams($bind, true);
        $where = implode(' ', $where);

        if ($bind) {
            $where = strtr($where, $bind);
        }

        return $where;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return array('dsn', 'user', 'password', 'params');
    }

    /**
     * Проснись
     */
    public function __wakeup()
    {
        $this->pdo = null;
        $this->isConnected = false;
    }

    /**
     * Quotes a value and places into a piece of text at a placeholder.
     *
     * The placeholder is a question-mark; all placeholders will be replaced
     * with the quoted value.   For example:
     *
     * <code>
     * $text = "WHERE date < ?";
     * $date = "2005-01-02";
     * $safe = $sql->quoteInto($text, $date);
     * // $safe = "WHERE date < '2005-01-02'"
     * </code>
     *
     * @param string  $text  The text with a placeholder.
     * @param mixed   $value The value to quote.
     * @param string  $type  OPTIONAL SQL datatype
     * @param integer $count OPTIONAL count of placeholders to replace
     * @return string An SQL-safe quoted value placed into the original text.
     */
    public function quoteInto($text, $value, $type = null, $count = null)
    {
        if ($count === null) {
            return str_replace('?', $this->quote($value, $type), $text);
        } else {
            while ($count > 0) {
                if (strpos($text, '?') !== false) {
                    $text = substr_replace($text, $this->quote($value, $type), strpos($text, '?'), 1);
                }
                --$count;
            }
            return $text;
        }
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.2.1)<br/>
     * Quotes a string for use in a query.
     * @link http://php.net/manual/en/pdo.quote.php
     * @param string $string <p>
     * The string to be quoted.
     * </p>
     * @param int $parameter_type [optional] <p>
     * Provides a data type hint for drivers that have alternate quoting styles.
     * </p>
     * @return string a quoted string that is theoretically safe to pass into an
     * SQL statement. Returns false if the driver does not support quoting in
     * this way.
     */
    public function _quote ($string, $parameter_type = PDO::PARAM_STR)
    {
        $this->connect();
        return $this->pdo->quote($string, $parameter_type);
    }

    /**
     * Safely quotes a value for an SQL statement.
     *
     * If an array is passed as the value, the array values are quoted
     * and then returned as a comma-separated string.
     *
     * @param mixed $value The value to quote.
     * @param mixed $type  OPTIONAL the SQL datatype name, or constant, or null.
     * @return mixed An SQL-safe quoted value (or string of separated values).
     */
    public function quote($value, $type = null)
    {
        if ($value instanceof Select) {
            return '(' . $value->assemble() . ')';
        }

        if ($value instanceof Expr) {
            return $value->__toString();
        }

        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = $this->quote($val, $type);
            }
            return implode(', ', $value);
        }

        if ($type !== null && array_key_exists($type = strtoupper($type), $this->_numericDataTypes)) {
            $quotedValue = '0';
            switch ($this->_numericDataTypes[$type]) {
                case self::INT_TYPE: // 32-bit integer
                    $quotedValue = (string) intval($value);
                    break;
                case self::BIGINT_TYPE: // 64-bit integer
                    // ANSI SQL-style hex literals (e.g. x'[\dA-F]+')
                    // are not supported here, because these are string
                    // literals, not numeric literals.
                    if (preg_match('/^(
                          [+-]?                  # optional sign
                          (?:
                            0[Xx][\da-fA-F]+     # ODBC-style hexadecimal
                            |\d+                 # decimal or octal, or MySQL ZEROFILL decimal
                            (?:[eE][+-]?\d+)?    # optional exponent on decimals or octals
                          )
                        )/x',
                        (string) $value, $matches)) {
                        $quotedValue = $matches[1];
                    }
                    break;
                case self::FLOAT_TYPE: // float or decimal
                    $quotedValue = sprintf('%F', $value);
            }
            return $quotedValue;
        }

        return $this->_quote($value);
    }

    /**
     * Quotes an identifier.
     *
     * Accepts a string representing a qualified indentifier. For Example:
     * <code>
     * $adapter->quoteIdentifier('myschema.mytable')
     * </code>
     * Returns: "myschema"."mytable"
     *
     * Or, an array of one or more identifiers that may form a qualified identifier:
     * <code>
     * $adapter->quoteIdentifier(array('myschema','my.table'))
     * </code>
     * Returns: "myschema"."my.table"
     *
     * The actual quote character surrounding the identifiers may vary depending on
     * the adapter.
     *
     * @param string|array|Expr $ident The identifier.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string The quoted identifier.
     */
    public function quoteIdentifier($ident, $auto=false)
    {
        return $this->_quoteIdentifierAs($ident, null, $auto);
    }

    /**
     * Quote an identifier and an optional alias.
     *
     * @param string|array|Expr $ident The identifier or expression.
     * @param string $alias An optional alias.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @param string $as The string to add between the identifier/expression and the alias.
     * @return string The quoted identifier and alias.
     */
    protected function _quoteIdentifierAs($ident, $alias = null, $auto = false, $as = ' AS ')
    {
        if ($ident instanceof Expr) {
            $quoted = $ident->__toString();
        } elseif ($ident instanceof Select) {
            $quoted = '(' . $ident->assemble() . ')';
        } else {
            if (is_string($ident)) {
                $ident = explode('.', $ident);
            }
            if (is_array($ident)) {
                $segments = array();
                foreach ($ident as $segment) {
                    if ($segment instanceof Expr) {
                        $segments[] = $segment->__toString();
                    } else {
                        $segments[] = $this->_quoteIdentifier($segment, $auto);
                    }
                }
                if ($alias !== null && end($ident) == $alias) {
                    $alias = null;
                }
                $quoted = implode('.', $segments);
            } else {
                $quoted = $this->_quoteIdentifier($ident, $auto);
            }
        }
        if ($alias !== null) {
            $quoted .= $as . $this->_quoteIdentifier($alias, $auto);
        }
        return $quoted;
    }

    /**
     * Quote an identifier.
     *
     * @param  string $value The identifier or expression.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string        The quoted identifier and alias.
     */
    protected function _quoteIdentifier($value, $auto=false)
    {
        if ($auto === false) {
            $q = '`';
            return ($q . str_replace("$q", "$q$q", $value) . $q);
        }
        return $value;
    }

    /**
     * Quote a table identifier and alias.
     *
     * @param string|array|Expr $ident The identifier or expression.
     * @param string $alias An alias for the table.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string The quoted identifier and alias.
     */
    public function quoteTableAs($ident, $alias = null, $auto = false)
    {
        return $this->_quoteIdentifierAs($ident, $alias, $auto);
    }

    /**
     * Quote a column identifier and alias.
     *
     * @param string|array|Expr $ident The identifier or expression.
     * @param string $alias An alias for the column.
     * @param boolean $auto If true, heed the AUTO_QUOTE_IDENTIFIERS config option.
     * @return string The quoted identifier and alias.
     */
    public function quoteColumnAs($ident, $alias, $auto=false)
    {
        return $this->_quoteIdentifierAs($ident, $alias, $auto);
    }
}