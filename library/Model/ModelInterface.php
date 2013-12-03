<?php

namespace Model;

use Model\Cond\AbstractCond as Cond;

interface ModelInterface
{
    public function prepareCond(Cond $cond = null, $entity = null, $type = null);

    public function filterOnAdd($data, Cond $cond = null);
    public function filterOnUpdate($data, Cond $cond = null);

    public function validateOnAdd(array $data);
    public function validateOnUpdate(array $data);

}