<?php

namespace Model\Filter;

class Int extends AbstractFilter
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
        return (int) ((string)$value);
    }
}
