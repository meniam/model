<?php

namespace Model\Paginator\Adapter;

use \Model\Db\Select;
use \Model\Db\Expr;
use \Model\Paginator\Exception\ErrorException;

/**
 * Description
 *
 * @category   Model
 * @package    Paginator
 * @author     Eugene Myazin <meniam@gmail.com>
 * @since      26.12.12 17:17
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Mysql implements AdapterInterface
{
    /**
     * Name of the row count column
     *
     * @var string
     */
    const ROW_COUNT_COLUMN = 'paginator_row_count';

    /**
     * @var \Model\Db\Select
     */
    private $select;

    /**
     * @var
     */
    private $countSelect;

    private $rowCount;

    public function __construct(Select $select)
    {
        $this->select = $select;
    }

    /**
     * Returns an collection of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->select->limit($itemCountPerPage, $offset);

        return $this->select->query()->fetchAll();
    }

    /**
     * Returns the total number of rows in the result set.
     *
     * @return integer
     */
    public function count()
    {
        if ($this->rowCount === null) {
            $this->setRowCount(
                $this->getCountSelect()
            );
        }

        return $this->rowCount;
    }

    /**
     * Sets the total row count, either directly or through a supplied
     * query.  Without setting this, {@link getPages()} selects the count
     * as a subquery (SELECT COUNT ... FROM (SELECT ...)).  While this
     * yields an accurate count even with queries containing clauses like
     * LIMIT, it can be slow in some circumstances.  For example, in MySQL,
     * subqueries are generally slow when using the InnoDB storage engine.
     * Users are therefore encouraged to profile their queries to find
     * the solution that best meets their needs.
     *
     * @param $rowCount
     * @throws \Model\Paginator\Exception\ErrorException
     * @internal                                     param int|\Model\Paginator\Adapter\Zend_Db_Select $totalRowCount Total row count integer
     *                                               or query
     * @return $this
     */
    public function setRowCount($rowCount)
    {
        if ($rowCount instanceof Select) {
            $columns = $rowCount->getPart(Select::COLUMNS);

            $countColumnPart = empty($columns[0][2])
                ? $columns[0][1]
                : $columns[0][2];

            if ($countColumnPart instanceof Expr) {
                $countColumnPart = $countColumnPart->__toString();
            }

            $rowCountColumn = self::ROW_COUNT_COLUMN;

            // The select query can contain only one column, which should be the row count column
            if (false === strpos($countColumnPart, $rowCountColumn)) {
                throw new ErrorException('Row count column not found');
            }

            $result = $rowCount->query(null)->fetch(\PDO::FETCH_ASSOC);

            $this->rowCount = count($result) > 0 ? $result[$rowCountColumn] : 0;
        } else if (is_integer($rowCount)) {
            $this->rowCount = $rowCount;
        } else {
            throw new ErrorException('Invalid row count');
        }

        return $this;
    }

    /**
     * Get the COUNT select object for the provided query
     *
     * In that use-case I'm expecting problems when either GROUP BY or DISTINCT
     * has one column.
     *
     * @return Select
     */
    public function getCountSelect()
    {
        /**
         * We only need to generate a COUNT query once. It will not change for
         * this instance.
         */
        if ($this->countSelect !== null) {
            return $this->countSelect;
        }

        $rowCount = clone $this->select;
        $rowCount->__toString(); // Workaround for ZF-3719 and related

        $db = $rowCount->getAdapter();

        $countColumn = $db->quoteIdentifier(self::ROW_COUNT_COLUMN);
        $countPart   = 'COUNT(1) AS ';
        $groupPart   = null;
        $unionParts  = $rowCount->getPart(Select::UNION);

        /**
         * If we're dealing with a UNION query, execute the UNION as a subquery
         * to the COUNT query.
         */
        if (!empty($unionParts)) {
            $expression = new Expr($countPart . $countColumn);

            $rowCount = new Select($db);
            $rowCount->bind($rowCount->getBind())
                     ->from($rowCount, $expression);
        } else {
            $columnParts = $rowCount->getPart(Select::COLUMNS);
            $groupParts  = $rowCount->getPart(Select::GROUP);
            $havingParts = $rowCount->getPart(Select::HAVING);
            $isDistinct  = $rowCount->getPart(Select::DISTINCT);

            /**
             * If there is more than one column AND it's a DISTINCT query, more
             * than one group, or if the query has a HAVING clause, then take
             * the original query and use it as a subquery os the COUNT query.
             */
            if (($isDistinct && ((count($columnParts) == 1 && $columnParts[0][1] == Select::SQL_WILDCARD)
                || count($columnParts) > 1)) || count($groupParts) > 1 || !empty($havingParts)) {
                $rowCount->reset(Select::ORDER);
                $rowCount = new Select($db);
                $rowCount->bind($rowCount->getBind())
                    ->from($rowCount);
            } else if ($isDistinct) {
                $part = $columnParts[0];

                if ($part[1] !== Select::SQL_WILDCARD && !($part[1] instanceof Expr)) {
                    $column = $db->quoteIdentifier($part[1], true);

                    if (!empty($part[0])) {
                        $column = $db->quoteIdentifier($part[0], true) . '.' . $column;
                    }

                    $groupPart = $column;
                }
            } else if (!empty($groupParts)) {
                $groupPart = $db->quoteIdentifier($groupParts[0], true);
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
            $expression = new Expr($countPart . $countColumn);

            $rowCount->reset(Select::COLUMNS)
                ->reset(Select::ORDER)
                ->reset(Select::LIMIT_OFFSET)
                ->reset(Select::GROUP)
                ->reset(Select::DISTINCT)
                ->reset(Select::HAVING)
                ->columns($expression);
        }

        $this->countSelect = $rowCount;

        return $rowCount;
    }
}