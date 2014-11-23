<?php

namespace Model\Filter;

class Null extends AbstractFilter
{
    public function filter($value)
    {
        return  empty($value) ? null : $value;
    }
}
