<?php

namespace Model\Filter;

abstract class AbstractFilter
{
    protected $options;

    public function setOptions($option = array())
    {
        $this->options = $option;
    }

    /**
     * @param $value
     * @return bool
     */
    abstract function filter($value);
}