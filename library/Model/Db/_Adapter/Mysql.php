<?php

namespace Model\Db\Adapter;

use Model\Cond as Cond;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;

use Model\AbstractModel;

class Mysql extends AbstractModel
{
    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    private static $db;

    /**
     * @var \Zend\Db\Sql\Sql
     */
    private static $sql;

    private function execute(Select $select = null, Cond $cond = null, $entity = null)
    {
        $items = array();
        $pager = null;

        $cond   = $this->prepareCond($cond);
        $select = $this->prepareSelect($cond, $select);

        if ($cond->checkCond(Cond::SHOW_QUERY)) {
            echo "<!-- SQL: " . $this->getSql()->getSqlStringForSqlObject($select) . "-->";
        }

        if ($cond->checkCond(Cond::PAGE)) {
            $pager = new \Zend\Paginator\Paginator(new \Zend\Paginator\Adapter\DbSelect($select, $this->getSql(), new ResultSet(ResultSet::TYPE_ARRAY, \ArrayObject::ARRAY_AS_PROPS)));
            $pager->setItemCountPerPage($cond->checkCond(Cond::ITEMS_PER_PAGE));
            $pager->setCurrentPageNumber($cond->checkCond(Cond::PAGE, 1));

            /** @var \Zend\Db\ResultSet\ResultSet $result  */
            $result = $pager->getCurrentItems();

            $items = $result->toArray();
        } else {
            $statement = $this->getDb()->createStatement();

            $select->prepareStatement($this->getDb(), $statement);
            $result = $statement->execute();

            while($row = $result->next()) {
                $items[] = $row;
            }
        }

        return array($items, $pager);
    }

    public function fetchOne(Select $select = null, Cond $cond = null, $entity = null)
    {
        list($items, $null) = $this->execute($select, $cond, $entity);

        if ($items) {
            $item = reset($items);

            return reset($item);
        }

        return null;
    }

    public function fetchRow(Select $select = null, Cond $cond = null, $entity = null)
    {
        list($items, $pager) = $this->execute($select, $cond, $entity);

        $prepareCallback = 'prepare' . $this->getName();

        if ($cond->checkCond(Cond::WITHOUT_PREPARE)) {
            return (array)$items;
        } else {
            return call_user_func(array($this, $prepareCallback), $items, $cond, $pager);
        }
    }

    public function fetchAll(Select $select = null, Cond $cond = null, $entity = null)
    {
        list($items, $pager) = $this->execute($select, $cond, $entity);

        $prepareCallback = 'prepare' . $this->getName() . 'Collection';

        if ($cond->checkCond(Cond::WITHOUT_PREPARE)) {
            return (array)$items;
        } else {
            return call_user_func(array($this, $prepareCallback), $items, $cond, $pager);
        }
    }

    public function fetchPairs(Select $select = null, Cond $cond = null)
    {
        list($items, $null) = $this->execute($select, $cond);

        $result = array();
        foreach ($items as $item) {
            if (count($item) > 1) {
                $result[reset($item)] = next($item);
            } else {
                $result[reset($item)] = reset($item);
            }
        }

        return $result;
    }

    public function fetchCount(Select $select = null, Cond $cond = null)
    {
        $items = array();
        $pager = null;

        $cond   = $this->prepareCond($cond);
        $select = $this->prepareCountSelect($this->prepareSelect($cond, $select));

        if ($cond->checkCond(Cond::SHOW_QUERY)) {
            echo "<!-- SQL: " . $this->getSql()->getSqlStringForSqlObject($select) . "-->";
        }

        $statement = $this->getDb()->createStatement();

        $select->prepareStatement($this->getDb(), $statement);
        $result = $statement->execute();

        print_r($result);
        die;
    }


    /**
     * @return \Zend\Db\Sql\Sql
     */
    public function getSql()
    {
        if (!self::$sql) {
            self::$sql = new Sql(self::$db);
        }

        return self::$sql;
    }

    const ROW_COUNT_COLUMN = 'row_count_column';

    protected function prepareCountSelect(Select $select)
    {
        $rowCount = clone $select;

        $db = $this->getDb();

        $countColumn = $db->platform->quoteIdentifier(self::ROW_COUNT_COLUMN);
        $countPart   = 'COUNT(1) AS ';
        $groupPart   = null;

        $columnParts = $rowCount->getRawState(Select::COLUMNS);
        $groupParts  = $rowCount->getRawState(Select::GROUP);
        $havingParts = $rowCount->getRawState(Select::HAVING);

        /**
         * If there is more than one column AND it's a DISTINCT query, more
         * than one group, or if the query has a HAVING clause, then take
         * the original query and use it as a subquery os the COUNT query.
         */
        if (count($groupParts) > 1 || $havingParts->count()) {
            //throw new \Model\Exception\ErrorException('Count doesnt work with having or group');
            //$rowCount = $db->select()->from($this->_select);

            $rowCount = new Select();
            $rowCount->from($select);
        } else if (!empty($groupParts) && $groupParts[0] !== Select::SQL_STAR &&
                !($groupParts[0] instanceof \Zend\Db\Sql\Predicate\Expression)) {
            $groupPart = $db->platform->quoteIdentifier($groupParts[0]);
        }

        /**
         * If the original query had a GROUP BY or a DISTINCT part and only
         * one column was specified, create a COUNT(DISTINCT ) query instead
         * of a regular COUNT query.
         */
        if (!empty($groupPart)) {
            $countPart = 'COUNT(DISTINCT ' . $groupPart . ') AS ';
        }

        /**
         * Create the COUNT part of the query
         */
        $expression = new \Zend\Db\Sql\Predicate\Expression($countPart . $countColumn);

        $rowCount->reset(Select::COLUMNS)
                ->reset(Select::ORDER)
                ->reset(Select::LIMIT)
                ->reset(Select::OFFSET)
                ->reset(Select::GROUP)
                ->reset(Select::HAVING)
                ->columns(array($expression));

        return $rowCount;
    }

    private function prepareSelect(Cond $cond, Select $select = null)
    {
        /** @var $select \Zend\Db\Sql\Select */
        $select = $select ?: $this->getSql()->select();

        /***************************************************************************************************************
         * FROM
         **************************************************************************************************************/
        if (($from = $cond->getCond(Cond::FROM))) {
            $select->from($from);
        }

        /***************************************************************************************************************
         * COLUMNS
         **************************************************************************************************************/
        if (($columns = $cond->getCond(Cond::COLUMNS))) {
            $columns = is_array($columns) ?: array($columns);

            $select->reset(\Zend\Db\Sql\Select::COLUMNS)->columns($columns);
        }

        /**********************************************************************
         * JOIN
         *********************************************************************/
        if ($cond->checkAnyJoin()) {
            $joinRules = $cond->getJoin();

            foreach ($joinRules as $join) {
                if (!$join->issetRule()) {
                    continue;
                }

                /* @var $joinFunc type */
                $joinFunc = Cond::$_joinTypes[$join->getJoinType()];
                $select->$joinFunc($join->getTable(), $join->getCondition(), $join->getColumns());
            }
        }

        /**********************************************************************
         * WHERE
         *********************************************************************/
        if (($where = $cond->getCond(Cond::WHERE))) {
            foreach ($where as $whereCond) {
                if (is_array($whereCond)) {
                    $select->where($whereCond['predicate'], $whereCond['combination']);
                }
            }
        }

        /**********************************************************************
         * GROUP
         *********************************************************************/
        if (($group = $cond->getCond(Cond::GROUP))) {
            foreach ($group as $_group) {
                $select->group($_group);
            }
        }

        /**********************************************************************
         * ORDER
         *********************************************************************/
        $order = $cond->getCond(Cond::ORDER);
        if (is_array($order) && !empty($order)) {
            foreach ($order as $_order) {
                if ($_order instanceof Zend_Db_Select) {
                    $_order = str_replace("`", '', Model_Select::fromZendDbSelect($_order)->renderOrder());
                }

                $select->order($_order);
            }
        }

        /**********************************************************************
         * PAGE
         *********************************************************************/
        if (!$cond->checkCond(Cond::PAGE)) {
            if ($cond->checkCond(Cond::LIMIT)) {
                if ($cond->checkCond(Cond::OFFSET)) {
                    $select->limit($cond->getCond(Cond::LIMIT), $cond->getCond(Cond::OFFSET));
                } else {
                    $select->limit($cond->getCond(Cond::LIMIT));
                }
            } else if ($cond->checkCond(Cond::OFFSET)) {
                $select->limit(0, $cond->getCond(Cond::OFFSET));
            }
        }

        return $select;
    }

    /**
     * Установить Db Adapter
     *
     * @param \Zend\Db\Adapter\Adapter $db
     */
    public static function setDb(\Zend\Db\Adapter\Adapter $db)
    {
        self::$db = $db;
    }

    /**
     *
     * @return \Zend\Db\Adapter\Adapter
     */
    public function getDb()
    {
        return self::$db;
    }
}