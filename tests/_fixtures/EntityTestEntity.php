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
            'inactive_reason' => self::DATA_TYPE_STRING,
            '_price'          => self::DATA_TYPE_INT,
            '_tag'            => 'Model\\Entity\\TagEntity',
        );
    }

    public function getName()
    {
        return $this->get('name');
    }

    public function getStatus()
    {
        return $this->get('status');
    }

    public function getPrice()
    {
        return $this->get('price');
    }

    public function getTag()
    {
        return $this->get('_tag');
    }
}
