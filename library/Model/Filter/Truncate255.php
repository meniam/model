<?php
namespace Model\Filter;

class Truncate255 extends Truncate
{
    public function filter($value)
    {
        $this->setLength(255);
        return parent::filter($value);
    }
}
