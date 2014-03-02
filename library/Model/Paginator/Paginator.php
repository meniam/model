<?php

namespace Model\Paginator;

use \Model\Paginator\Adapter\AdapterInterface;
use \Model\Paginator\ScrollingStyle\ScrollingStyleInterface;

class Paginator implements \Countable
{
    private $itemCountPerPage = 10;

    private $currentPage = 1;

    private $totalItemCount;

    private $pageCount;

    private $pages;

    private $pageRange;

    private $defaultPageRange = 11;

    protected static $defaultScrollingStyle = 'Sliding';

    private $currentItems;

    private $currentItemCount;

    private $adapter;

    public function __construct(AdapterInterface $adapter = null)
    {
        if (!$adapter) {
            $adapter = new \Model\Paginator\Adapter\Null();
        }
        $this->adapter = $adapter;
    }

    /**
     * @param int $itemCountPerPage
     *
     * @return $this
     */
    public function setItemCountPerPage($itemCountPerPage = 10)
    {
        $this->itemCountPerPage = $itemCountPerPage;
        return $this;
    }

    public function getItemCountPerPage()
    {
        return $this->itemCountPerPage;
    }

    /**
     * Установить текущую страницу
     *
     * @param $currentPage
     * @return Paginator
     */
    public function setCurrentPageNumber($currentPage)
    {
        $this->currentPage = $currentPage;
        return $this;
    }

    public function getCurrentPageNumber()
    {
        return $this->currentPage;
    }

    /**
     * Посчитать количество страниц
     *
     * @return int
     */
    public function count()
    {
        if (!$this->pageCount) {
            $this->pageCount = $this->calculatePageCount();
        }

        return $this->pageCount;
    }

    /**
     * @return integer
     */
    private function calculatePageCount()
    {
        return ceil((integer) $this->getTotalItemCount() / $this->getItemCountPerPage());
    }

    /**
     * @param int $totalItemCount
     *
     * @return $this
     */
    public function setTotalItemCount($totalItemCount = 0)
    {
        $this->totalItemCount = (int)$totalItemCount;
        return $this;
    }

    /**
     * @return integer
     */
    public function getTotalItemCount()
    {
        if (!$this->totalItemCount) {
            $this->totalItemCount = count($this->adapter);
        }

        return $this->totalItemCount;
    }

    /**
     * @return \StdClass
     */
    public function getPages()
    {
        if (!$this->pages) {
            $this->pages = $this->createPages();
        }

        return $this->pages;
    }

    /**
     * Returns a subset of pages within a given range.
     *
     * @param  integer $lowerBound Lower bound of the range
     * @param  integer $upperBound Upper bound of the range
     * @return array
     */
    public function getPagesInRange($lowerBound, $upperBound)
    {
        $lowerBound = $this->normalizePageNumber($lowerBound);
        $upperBound = $this->normalizePageNumber($upperBound);

        $pages = array();

        for ($pageNumber = $lowerBound; $pageNumber <= $upperBound; $pageNumber++) {
            $pages[$pageNumber] = $pageNumber;
        }

        return $pages;
    }

    /**
     * Brings the page number in range of the paginator.
     *
     * @param  integer $pageNumber
     * @return integer
     */
    public function normalizePageNumber($pageNumber)
    {
        $pageNumber = (integer) $pageNumber;

        if ($pageNumber < 1) {
            $pageNumber = 1;
        }

        $pageCount = $this->calculatePageCount();

        if ($pageCount > 0 && $pageNumber > $pageCount) {
            $pageNumber = $pageCount;
        }

        return $pageNumber;
    }

    /**
     * Brings the item number in range of the page.
     *
     * @param  integer $itemNumber
     * @return integer
     */
    public function normalizeItemNumber($itemNumber)
    {
        $itemNumber = (integer) $itemNumber;

        if ($itemNumber < 1) {
            $itemNumber = 1;
        }

        if ($itemNumber > $this->getItemCountPerPage()) {
            $itemNumber = $this->getItemCountPerPage();
        }

        return $itemNumber;
    }

    /**
     * Returns the number of items in a collection.
     *
     * @param  mixed $items Items
     * @return integer
     */
    public function getItemCount($items)
    {
        $itemCount = 0;

        if (is_array($items) || $items instanceof \Countable) {
            $itemCount = count($items);
        } else { // $items is something like LimitIterator
            $itemCount = iterator_count($items);
        }

        return $itemCount;
    }
    /**
     * Returns the number of items for the current page.
     *
     * @return integer
     */
    public function getCurrentItemCount()
    {
        if ($this->currentItemCount === null) {
            $this->currentItemCount = $this->getItemCount($this->getCurrentItems());
        }

        return $this->currentItemCount;
    }

    /**
     * Returns the items for the current page.
     *
     * @return Traversable
     */
    public function getCurrentItems()
    {
        if ($this->currentItems === null) {
            $this->currentItems = $this->getItemsByPage($this->getCurrentPageNumber());
        }

        return $this->currentItems;
    }

    /**
     * Returns the items for a given page.
     *
     * @return Traversable
     */
    public function getItemsByPage($pageNumber)
    {
        $pageNumber = $this->normalizePageNumber($pageNumber);
        $offset = ($pageNumber - 1) * $this->getItemCountPerPage();
        $items = $this->adapter->getItems($offset, $this->getItemCountPerPage());

        if (!$items instanceof \Traversable) {
            $items = new \ArrayIterator($items);
        }

        return $items;
    }

    protected function createPages($scrollingStyle = null)
    {
        $pageCount = $this->calculatePageCount();
        $currentPageNumber = $this->currentPage;

        $pages = new \StdClass();
        $pages->pageCount = $pageCount;
        $pages->itemCountPerPage = $this->getItemCountPerPage();
        $pages->first = 1;
        $pages->current = $currentPageNumber;
        $pages->last = $pageCount;

        // Previous and next
        if ($currentPageNumber - 1 > 0) {
            $pages->previous = $currentPageNumber - 1;
        }

        if ($currentPageNumber + 1 <= $pageCount) {
            $pages->next = $currentPageNumber + 1;
        }

        // Pages in range
        $scrollingStyle          = new \Model\Paginator\ScrollingStyle\Sliding($this);
        $pages->pagesInRange     = $scrollingStyle->getPages($this);
        $pages->firstPageInRange = min($pages->pagesInRange);
        $pages->lastPageInRange  = max($pages->pagesInRange);

        // Item numbers
        if ($this->getCurrentItems() !== null) {
            $pages->currentItemCount = $this->getCurrentItemCount();
            $pages->itemCountPerPage = $this->getItemCountPerPage();
            $pages->totalItemCount   = $this->getTotalItemCount();
            $pages->firstItemNumber  = (($currentPageNumber - 1) * $this->getItemCountPerPage()) + 1;
            $pages->lastItemNumber   = $pages->firstItemNumber + $pages->currentItemCount - 1;
        }

        return $pages;
    }

    /**
     *
     * @return int
     */
    public function getPageRange()
    {
        if (!$this->pageRange) {
            $this->pageRange = $this->defaultPageRange;
        }

        return $this->pageRange;
    }
}