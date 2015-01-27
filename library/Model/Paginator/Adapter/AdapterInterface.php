<?php

namespace Model\Paginator\Adapter;

interface AdapterInterface extends \Countable
{
    /**
     * Returns the total number of rows in the result set.
     *
     * @return integer
     */
    public function count();

}