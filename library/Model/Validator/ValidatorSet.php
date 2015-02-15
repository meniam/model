<?php

namespace Model\Validator;

use Model\Model;

class ValidatorSet
{
    /**
     * @var array
     */
    private $validatorList = array();

    /**
     * @var array
     */
    private $data = array();

    /**
     * @var null|bool
     */
    private $isValid = null;

    /**
     * @var mixed
     */
    private $result = null;

    /**
     * @param array $validatorList
     * @param array $data
     * @param array $requiredFields
     */
    public function __construct($validatorList = array(), $data = array(), $requiredFields = array())
    {
        $this->setValidatorList($validatorList);
        $this->addNotEmptyValidatorList($requiredFields);
        $this->setData($data);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        if (!is_null($this->isValid)) {
            return $this->isValid;
        }

        $this->result = Model::getValidatorAdapter()->validate($this->getValidatorList(), $this->data);
        $this->isValid = Model::getValidatorAdapter()->isValid($this->result);

        return $this->isValid;
    }

    /**
     * @param array $validatorRequiredFields
     * @return $this
     */
    protected function addNotEmptyValidatorList(array $validatorRequiredFields)
    {
        foreach ($validatorRequiredFields as $field) {
            array_unshift($this->validatorList[$field], Model::getValidatorAdapter()->getNotEmptyValidator());
        }

        return $this;
    }

    /**
     * @param array $validatorList
     * @return $this
     */
    protected function setValidatorList(array $validatorList)
    {
        foreach ($validatorList as $field => $validators) {
            foreach ($validators as $validator) {
                $this->validatorList[$field][] = clone $validator;
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getValidatorList()
    {
        return $this->validatorList;
    }

    /**
     * @param array $data
     * @return $this
     */
    protected function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get decorated message array
     *
     * @return array
     */
    public function getMessageArray()
    {
        return Model::getValidatorAdapter()->getValidatorMessages($this->result);
    }
}