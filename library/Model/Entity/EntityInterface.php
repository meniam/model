<?php

namespace Model\Entity;

interface EntityInterface
{
    public function __construct($data = array());
    public function get($name);
    public function toArray();
    public function toArrayWithoutRelated();


    public function getId();
    public function exists();
    //public function equals(EntityInterface $value);
}