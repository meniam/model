<?php

namespace Model\Filter;

abstract class AbstractFilter
{

    /**
     * @param $value
     * @return bool
     */
    abstract function filter($value);
}