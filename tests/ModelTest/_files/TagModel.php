<?php

namespace Model;

use \Model\Cond\ProductCond as Cond;

class TagModel extends \Model\AbstractModel
{
    public function init()
    {
        $this->setName('product');
    }

    public function prepareProduct($data, Cond $cond = null)
    {
        return $data;
    }

    public function prepareProductCollection($data, Cond $cond = null, $pager = null)
    {
        return $data;
    }
}