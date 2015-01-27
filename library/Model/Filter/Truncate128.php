<?php
namespace Model\Filter;

class Truncate128 extends Truncate
{
    public function filter($value)
    {
        $this->setLength(128);
        return parent::filter($value);
    }
}
