<?php

namespace Model\Filter;

use Zend\Filter\AbstractFilter;

/**
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Abs extends AbstractFilter
{
    /**
     * Defined by Zend_Filter_Interface
     *
     * Returns (int) $value
     *
     * @param  mixed $value
     * @return int
     */
    public function filter($value)
    {
        return abs($value);
    }
}
