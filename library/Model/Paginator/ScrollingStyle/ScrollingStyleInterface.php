<?php

namespace Model\Paginator\ScrollingStyle;

use Model\Paginator\Paginator;

interface ScrollingStyleInterface
{
    /**
     * Returns an array of "local" pages given a page number and range.
     *
     * @param  Paginator $paginator
     * @param  integer $pageRange (Optional) Page range
     * @return array
     */
    public function getPages(Paginator $paginator, $pageRange = null);
}