<?php

namespace Model\Db\Mysql;

use Model\Db\Mysql\AbstractSql;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Having;

class Select extends AbstractSql
{
    /**#@+
     * Constant
     * @const
     */
    const SELECT           = 'select';
    const COLUMNS          = 'columns';
    const FROM             = 'from';
    const JOINS            = 'joins';
    const WHERE            = 'where';
    const DISTINCT         = 'distinct';
    const GROUP            = 'group';
    const HAVING           = 'having';
    const ORDER            = 'order';
    const LIMIT            = 'limit';
    const OFFSET           = 'offset';
    const JOIN_INNER       = 'inner';
    const JOIN_OUTER       = 'outer';
    const JOIN_LEFT        = 'left';
    const JOIN_RIGHT       = 'right';
    const SQL_STAR         = '*';
    const ORDER_ASCENDING  = 'ASC';
    const ORDER_DESCENDING = 'DESC';
    /**#@-*/

    /**
     * @var mixed
     */
    protected $from;

    /**
     * Колонки для выбора
     *
     * @var string
     */
    protected $columns = array(self::SQL_STAR);

    /**
     * Is distinct
     *
     * @var bool
     */
    protected $distinct = false;

    /**
     * @var array
     */
    protected $joins = array();

    /**
     * @var \Zend\Db\Sql\Where
     */
    protected $where = null;

    /**
     * @var \Zend\Db\Sql\Having
     */
    protected $having = null;

    /**
     * @var null|string
     */
    protected $order = array();

    /**
     * @var null|array
     */
    protected $group = null;

    /**
     * @var int|null
     */
    protected $limit = null;

    /**
     * @var int|null
     */
    protected $offset = null;

    /**
     *
     * @var bool
     */
    protected $prefixColumnsWithTable = true;

    public function __construct($from = null, $alias = null)
    {
        if ($from) {
            $this->from($from, $alias);
        }

        $this->where  = new Where();
        $this->having = new Having();
    }

    /**
     * Включить DISTINCT режим
     *
     * @return Select
     */
    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    /**
     *
     * @param  array $columns
     * @param bool   $prefixColumnsWithTable
     * @return Select
     */
    public function columns(array $columns, $prefixColumnsWithTable = true)
    {
        $this->columns = $columns;
        $this->prefixColumnsWithTable = (bool) $prefixColumnsWithTable;
        return $this;
    }

    /**
     * Установить FROM
     *
     * @param      $from
     * @param null $alias
     * @return Select
     */
    public function from($from, $alias = null)
    {
        if ($alias) {
            $from = array((string)$alias => $from);
        }

        $this->from = $from;
        return $this;
    }

    /**
     * @param array|string $group
     * @return Select
     */
    public function group($group)
    {
        if (is_string($group)) {
            if (strpos($group, ',') !== false) {
                $group = preg_split('#,\s+#', $group);
            } else {
                $group = (array) $group;
            }
        }

        foreach ($group as $v) {
            $this->group[] = $v;
        }
        return $this;
    }

    /**
     * @param string|array $order
     * @return Select
     */
    public function order($order)
    {
        if (is_string($order)) {
            if (strpos($order, ',') !== false) {
                $order = preg_split('#,\s+#', $order);
            } else {
                $order = (array) $order;
            }
        }
        foreach ($order as $k => $v) {
            if (is_string($k)) {
                $this->order[$k] = $v;
            } else {
                $this->order[] = $v;
            }
        }
        return $this;
    }

    /**
     * @param int $limit
     * @return Select
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * @param int $offset
     * @return Select
     */
    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }


    /**
     * Create where clause
     *
     * @param  Where|\Closure|string|array $predicate
     * @param  string $combination One of the OP_* constants from Predicate\PredicateSet
     * @return Select
     */
    public function having($predicate, $combination = \Zend\Db\Sql\Predicate\PredicateSet::OP_AND)
    {
        if ($predicate instanceof Having) {
            $this->having = $predicate;
        } elseif ($predicate instanceof \Closure) {
            $predicate($this->having);
        } else {
            if (is_string($predicate)) {
                $predicate = new \Zend\Db\Sql\Predicate\Expression($predicate);
                $this->having->addPredicate($predicate, $combination);
            } elseif (is_array($predicate)) {
                foreach ($predicate as $pkey => $pvalue) {
                    if (is_string($pkey) && strpos($pkey, '?') !== false) {
                        $predicate = new \Zend\Db\Sql\Predicate\Expression($pkey, $pvalue);
                    } elseif (is_string($pkey)) {
                        $predicate = new \Zend\Db\Sql\Predicate\Operator($pkey, \Zend\Db\Sql\Predicate\Operator::OP_EQ, $pvalue);
                    } else {
                        $predicate = new \Zend\Db\Sql\Predicate\Expression($pvalue);
                    }
                    $this->having->addPredicate($predicate, $combination);
                }
            }
        }
        return $this;
    }

    /**
     * Create where clause
     *
     * @param  \Zend\Db\Sql\Where|\Closure|string|array $predicate
     * @param  string $combination One of the OP_* constants from Predicate\PredicateSet
     * @return Select
     */
    public function where($predicate, $combination = \Zend\Db\Sql\Predicate\PredicateSet::OP_AND)
    {
        if ($predicate instanceof Where) {
            $this->where = $predicate;
        } elseif ($predicate instanceof \Closure) {
            $predicate($this->where);
        } else {
            if (is_string($predicate)) {
                // String $predicate should be passed as an expression
                $predicate = new \Zend\Db\Sql\Predicate\Expression($predicate);
                $this->where->addPredicate($predicate, $combination);
            } elseif (is_array($predicate)) {

                foreach ($predicate as $pkey => $pvalue) {
                    // loop through predicates

                    if (is_string($pkey) && strpos($pkey, '?') !== false) {
                        // First, process strings that the abstraction replacement character ?
                        // as an Expression predicate
                        $predicate = new \Zend\Db\Sql\Predicate\Expression($pkey, $pvalue);

                    } elseif (is_string($pkey)) {
                        // Otherwise, if still a string, do something intelligent with the PHP type provided

                        if (is_null($pvalue)) {
                            // map PHP null to SQL IS NULL expression
                            $predicate = new \Zend\Db\Sql\Predicate\IsNull($pkey, $pvalue);
                        } elseif (is_array($pvalue)) {
                            // if the value is an array, assume IN() is desired
                            $predicate = new \Zend\Db\Sql\Predicate\In($pkey, $pvalue);
                        } else {
                            // otherwise assume that array('foo' => 'bar') means "foo" = 'bar'
                            $predicate = new \Zend\Db\Sql\Predicate\Operator($pkey, \Zend\Db\Sql\Predicate\Operator::OP_EQ, $pvalue);
                        }
                    } elseif ($pvalue instanceof \Zend\Db\Sql\Predicate\PredicateInterface) {
                        // Predicate type is ok
                        $predicate = $pvalue;
                    } else {
                        // must be an array of expressions (with int-indexed array)
                        $predicate = new \Zend\Db\Sql\Predicate\Expression($pvalue);
                    }
                    $this->where->addPredicate($predicate, $combination);
                }
            }
        }
        return $this;
    }

    /**
     * Create join clause
     *
     * @param  string|array $name
     * @param  string       $on
     * @param  string|array $columns
     * @param  string       $type one of the JOIN_* constants
     * @throws Exception\InvalidArgumentException
     * @return Select
     */
    public function join($name, $on, $columns = self::SQL_STAR, $type = self::JOIN_INNER)
    {
        if (is_array($name) && (!is_string(key($name)) || count($name) !== 1)) {
            throw new Exception\InvalidArgumentException('join() expects $name as an array is a single element associative array');
        }
        if (!is_array($columns)) {
            $columns = array($columns);
        }
        $this->joins[] = array(
            'name'    => $name,
            'on'      => $on,
            'columns' => $columns,
            'type'    => $type
        );
        return $this;
    }

    public function getSql()
    {
        $sql = "SELECT ";

        list($columns, $table) = $this->precessFrom();

        if (is_array($columns)) {
            foreach ($columns as $column) {
                if (isset($column[1]) && $column[0] != $column[1]) {
                    $sqlParams[] = $column[0] . ' AS ' . $column[1];
                } else {
                    $sqlParams[] = $column[0];
                }
            }

            $sql .= implode(', ', $sqlParams);
        }
        $sql .= " FROM " . $table;

        if (($joins = $this->processJoins())) {
            foreach ($joins as $join) {
                list($joinType, $joinTable, $joinCondition) = $join;

                $sql .= " JOIN {$joinType} {$joinTable} ON ({$joinCondition})";
            }
        }

        if ($this->where->count() > 0) {
            $where = $this->processExpression($this->where)->getSql();

            $sql .= ' WHERE ' . $where;
        }

        return $sql;
    }


    /**
     * Обработка from
     *
     * @return null
     */
    protected function precessFrom()
    {
        $expr = 1;

        if (!$this->from) {
            return null;
        }

        $table  = $this->from;
        $schema = null;
        $alias  = null;

        if (is_array($table)) {
            $alias = key($table);
            $table = current($table);
        }

        $fromSubselect = false;
        if ($table instanceof Select) {
            $fromSubselect = true;
            $table = '(' . $this->processSubselect($table) . ')';
        } elseif ($table instanceof \Zend\Db\Sql\Expression) {
            $fromSubselect = true;
            $table = $this->processExpression($table)->getSql();
        } else {
            $table = $this->quoteIdentifier($table);

        }

        if ($schema) {
            $table = $this->quoteIdentifier($schema) . '.' . $table;
        }

        if ($alias) {
            $fromTable = $this->quoteIdentifier($alias);
            $table .= ' AS ' . $fromTable;
        } else {
            $fromTable = ($this->prefixColumnsWithTable && !$fromSubselect) ? $table : '';

        }

        if ($fromTable) {
            $fromTable .= '.';
        }

        // process table columns
        $columns = array();
        foreach ($this->columns as $columnIndexOrAs => $column) {

            $columnName = '';
            if ($column === self::SQL_STAR) {
                $columns[] = array($fromTable . self::SQL_STAR);
                continue;
            }

            if ($column instanceof \Zend\Db\Sql\Expression) {
                $columnParts = $this->processExpression($column);
                $columnName .= $columnParts->getSql();
            } else {
                $columnName .= $fromTable . $this->quoteIdentifier($column);
            }

            // process As portion
            if (is_string($columnIndexOrAs)) {
                $columnAs = $this->quoteIdentifier($columnIndexOrAs);
            } elseif (stripos($columnName, ' as ') === false) {
                $columnAs = (is_string($column)) ? $this->quoteIdentifier($column) : 'Expression' . $expr++;
            }
            $columns[] = (isset($columnAs)) ? array($columnName, $columnAs) : array($columnName);
        }

        $separator = '.';

        // process join columns
        foreach ($this->joins as $join) {
            foreach ($join['columns'] as $jKey => $jColumn) {
                $jColumns = array();
                if ($jColumn instanceof \Zend\Db\Sql\Expression) {
                    $jColumnParts = $this->processExpression($jColumn);
                    $jColumns[] = $jColumnParts->getSql();
                } else {
                    $name = (is_array($join['name'])) ? key($join['name']) : $name = $join['name'];
                    $jColumns[] = $this->quoteIdentifier($name) . $separator . $this->quoteIdentifierInFragment($jColumn);
                }
                if (is_string($jKey)) {
                    $jColumns[] = $this->quoteIdentifier($jKey);
                } elseif ($jColumn !== self::SQL_STAR) {
                    $jColumns[] = $this->quoteIdentifier($jColumn);
                }
                $columns[] = $jColumns;
            }
        }

        return array($columns, $table);
    }

    protected function processWhere()
    {
        if ($this->where->count() == 0) {
            return null;
        }
        $whereParts = $this->processExpression($this->where);

        return array($whereParts->getSql());
    }

    protected function processJoins()
    {
        if (!$this->joins) {
            return null;
        }

        // process joins
        $joinSpecArgArray = array();
        foreach ($this->joins as $j => $join) {
            $joinSpecArgArray[$j] = array();
            // type
            $joinSpecArgArray[$j][] = strtoupper($join['type']);
            // table name
            $joinSpecArgArray[$j][] = (is_array($join['name']))
                ? $this->quoteIdentifier(current($join['name'])) . ' AS ' . $this->quoteIdentifier(key($join['name']))
                : $this->quoteIdentifier($join['name']);
            // on expression
            $joinSpecArgArray[$j][] = ($join['on'] instanceof \Zend\Db\Sql\Expression)
                ? $this->processExpression($join['on'])
                : $this->quoteIdentifierInFragment($join['on'], array('=', 'AND', 'OR', '(', ')', 'BETWEEN')); // on
            if ($joinSpecArgArray[$j][2] instanceof \Zend\Db\Adapter\StatementContainerInterface) {
                $joinSpecArgArray[$j][2] = $joinSpecArgArray[$j][2]->getSql();
            }
        }

        return $joinSpecArgArray;
    }

    protected function processExpression(\Zend\Db\Sql\ExpressionInterface $expression)
    {
        // static counter for the number of times this method was invoked across the PHP runtime
        static $runtimeExpressionPrefix = 0;

        $sql = '';
        $statementContainer = new \Zend\Db\Adapter\StatementContainer;
        //$parameterContainer = $statementContainer->getParameterContainer();

        // initialize variables
        $parts = $expression->getExpressionData();
        //$expressionParamIndex = 1;

        foreach ($parts as $part) {

            // if it is a string, simply tack it onto the return sql "specification" string
            if (is_string($part)) {
                $sql .= $part;
                continue;
            }

            if (!is_array($part)) {
                throw new Exception\RuntimeException('Elements returned from getExpressionData() array must be a string or array.');
            }

            // process values and types (the middle and last position of the expression data)
            $values = $part[1];
            $types = (isset($part[2])) ? $part[2] : array();
            foreach ($values as $vIndex => $value) {
                if (isset($types[$vIndex]) && $types[$vIndex] == \Zend\Db\Sql\ExpressionInterface::TYPE_IDENTIFIER) {
                    $values[$vIndex] = $this->quoteIdentifierInFragment($value);
                } elseif (isset($types[$vIndex]) && $types[$vIndex] == \Zend\Db\Sql\ExpressionInterface::TYPE_VALUE) {
                    // if not a preparable statement, simply quote the value and move on
                    $values[$vIndex] = $this->quote($value);
                } elseif (isset($types[$vIndex]) && $types[$vIndex] == \Zend\Db\Sql\ExpressionInterface::TYPE_LITERAL) {
                    $values[$vIndex] = $value;
                } elseif (isset($types[$vIndex]) && $types[$vIndex] == \Zend\Db\Sql\ExpressionInterface::TYPE_SELECT) {
                    $values[$vIndex] = '(' . $this->processSubSelect($value) . ')';
                }
            }

            // after looping the values, interpolate them into the sql string (they might be placeholder names, or values)
            $sql .= vsprintf($part[0], $values);
        }

        $statementContainer->setSql($sql);
        return $statementContainer;
    }

    /**
     * @param Select $subselect
     * @return string
     */
    protected function processSubSelect(Select $subselect)
    {
        return $subselect->getSql();
    }

    /**
     * @param string $part
     * @return Select
     * @throws Exception\InvalidArgumentException
     */
    public function reset($part)
    {
        switch ($part) {
            case self::FROM:
                $this->from = null;
                break;
            case self::DISTINCT:
                $this->distinct = null;
                break;
            case self::COLUMNS:
                $this->columns = array();
                break;
            case self::JOINS:
                $this->joins = array();
                break;
            case self::WHERE:
                $this->where = new Where;
                break;
            case self::GROUP:
                $this->group = null;
                break;
            case self::ORDER:
                $this->order = null;
                break;
            case self::LIMIT:
                $this->limit = null;
                break;
            case self::OFFSET:
                $this->offset = null;
                break;
        }
        return $this;
    }

    /**
     * Get raw state of SQL part
     *
     * @param string|null $key
     * @return array|int|mixed|null|string|\Zend\Db\Sql\Where
     */
    public function getRawState($key = null)
    {
        switch ($key) {
            case self::FROM:
                return $this->from;
                break;
            case self::DISTINCT:
                return (bool)$this->distinct;
                break;
            case self::COLUMNS:
                return $this->columns;
                break;
            case self::JOINS:
                return $this->joins;
                break;
            case self::HAVING:
                return $this->having;
                break;
            case self::WHERE:
                return $this->where;
                break;
            case self::GROUP:
                return $this->group;
                break;
            case self::ORDER:
                return $this->order;
                break;
            case self::LIMIT:
                return $this->limit;
                break;
            case self::OFFSET:
                return $this->offset;
                break;
            default:
                return array(
                    self::FROM    => $this->from,
                    self::DISTINCT => $this->distinct,
                    self::COLUMNS  => $this->columns,
                    self::JOINS    => $this->joins,
                    self::HAVING   => $this->having,
                    self::WHERE    => $this->where,
                    self::ORDER    => $this->order,
                    self::GROUP    => $this->group,
                    self::HAVING   => $this->having,
                    self::LIMIT    => $this->limit,
                    self::OFFSET   => $this->offset
                );
        }
    }

    /**
     * Variable overloading
     *
     * Proxies to "where" only
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        switch (strtolower($name)) {
            case 'where':
                return $this->where;
            case 'having':
                return $this->having;
            default:
                throw new \Model\Db\Exception\InvalidArgumentException('Not a valid magic property for this object');
        }
    }

    /**
     * __clone
     *
     * Resets the where object each time the Select is cloned.
     *
     * @return void
     */
    public function __clone()
    {
        $this->where = clone $this->where;
        $this->having = clone $this->having;
    }

       /**
     * Quote value
     *
     * @param mixed $valueList
     * @return string
     */
    public function quote($valueList)
    {
        $valueList = str_replace('\'', '\\' . '\'', $valueList);

        if (is_array($valueList)) {
            $valueList = implode('\', \'', $valueList);
        }
        return '\'' . $valueList . '\'';
    }

    /**
     * Quote identifier
     *
     * @param string|string[]  $identifier
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        $identifier = str_replace('`', '\\`', $identifier);
        if (is_array($identifier)) {
            $identifier = implode('`.`', $identifier);
        }
        return '`' . $identifier . '`';
    }

    /**
     * Quote identifier in fragment
     *
     * @param  string $identifier
     * @param  array $safeWords
     * @return string
     */
    public function quoteIdentifierInFragment($identifier, array $safeWords = array())
    {
        $parts = preg_split('#([\.\s\W])#', $identifier, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $i => $part) {
            if ($safeWords && in_array($part, $safeWords)) {
                continue;
            }
            switch ($part) {
                case ' ':
                case '.':
                case '*':
                case 'AS':
                case 'As':
                case 'aS':
                case 'as':
                    break;
                default:
                    $parts[$i] = '`' . str_replace('`', '\\' . '`', $part) . '`';
            }
        }
        return implode('', $parts);
    }
}