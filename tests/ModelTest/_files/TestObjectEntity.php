<?php

class TestObjectEntity extends \Model\Entity\AbstractEntity
{
    protected function _setupPkFields()
    {
        $this->pkFields = array('a', 'b');
    }

    public function getId()
    {
        return $this->get('id');
    }

    protected function setupDataTypes()
    {
        $this->dataTypes = array(
            'mongo_id'   => 'MongoId',
            'id'    => Model\Entity\AbstractEntity::DATA_TYPE_INT,
            'test1' => Model\Entity\AbstractEntity::DATA_TYPE_ARRAY,
            'a'     => Model\Entity\AbstractEntity::DATA_TYPE_BOOL,
            'b'     => Model\Entity\AbstractEntity::DATA_TYPE_FLOAT,
            'c'     => Model\Entity\AbstractEntity::DATA_TYPE_INT,
            'd'     => Model\Entity\AbstractEntity::DATA_TYPE_NULL,
            'e'     => Model\Entity\AbstractEntity::DATA_TYPE_STRING,
            '_rel'  => Model\Entity\AbstractEntity::DATA_TYPE_INT,
            '_test_empty' => 'TestEmptyEntity'
        );
    }

    public function getTestEmpty()
    {
        return $this->get('_test_empty');
    }

    public function getTest1()
    {
        return $this->get('test1');
    }

    public function getRel()
    {
        return $this->get('_rel');
    }

    public function getA($default = null)
    {
        return $this->get('a', $default);
    }

    public function getB()
    {
        return $this->get('b');
    }

    public function getC()
    {
        return $this->get('c');
    }

    public function getD()
    {
        return $this->get('d');
    }

    public function getE()
    {
        return $this->get('e');
    }

    public function getMongoId()
    {
        return $this->get('mongo_id');
    }
}
