<?php

namespace Model\Filter;

class StringTrim extends \Zend\Filter\StringTrim
{
    /**
     * Unicode aware trim method
     * Fixes a PHP problem
     *
     * @param string $value
     * @param string $charlist
     * @return string
     */
    protected function unicodeTrim($value, $charlist = null)
    {
        return ($charlist === null) ? trim($value) : trim($value, $charlist);
    }
}
