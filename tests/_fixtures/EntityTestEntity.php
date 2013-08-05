<?php

namespace Model\Entity;

class EntityTestEntity extends AbstractEntity
{
    protected function setupDataTypes()
    {
        $this->dataTypes = array(
            'id'              => self::DATA_TYPE_INT,
            'name'            => self::DATA_TYPE_STRING,
            'price'           => self::DATA_TYPE_FLOAT,
            'is_show'         => self::DATA_TYPE_BOOL,
            'create_date'     => self::DATA_TYPE_STRING,
            'modify_date'     => self::DATA_TYPE_STRING,
            'status'          => self::DATA_TYPE_STRING,
            'inactive_reason' => self::DATA_TYPE_STRING);
    }

    public function getName()
    {
        return $this->get('name');
    }

    public function getStatus()
    {
        return $this->get('statis');
    }

    public function getPrice()
    {
        return $this->get('price');
    }
}
