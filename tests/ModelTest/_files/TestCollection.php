<?php

class TestCollection extends \Model\Collection\AbstractCollection
{
    public function __construct($data = null)
    {
        if ($data) {
            parent::__construct($data);
        }
    }

    public function __set($k, $v)
    {
        $this[$k] = $v;
    }
}
