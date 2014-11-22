<?php

namespace Model\Filter;

class Id extends AbstractFilter
{
    /**
     * Defined by Zend_Filter_Interface
     *
     * Returns (int) $value
     *
     * @param  mixed $value
     * @return integer
     */
    public function filter($value)
    {
        return abs((int)$value);
    }
}
