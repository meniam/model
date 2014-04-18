<?php

namespace Model\Filter;

class PlainText extends EntityDecode
{
    public function filter($value)
    {
        $value = parent::filter($value);
        $value = strip_tags($value);
        $value = htmlspecialchars_decode($value);
        $value =  trim($value);
        
        return $value;
    }
}