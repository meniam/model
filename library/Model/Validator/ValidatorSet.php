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

    private $requiredFields = array();

    /**
     * @param array $validatorList
     * @param array $data
     * @param array $requiredFields
     */
    public function __construct($validatorList = array(), $data = array(), $requiredFields = array())
    {
        $this->requiredFields = $requiredFields;

        $this->setValidatorList($validatorList);
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

        $this->validate();

        return $this->isValid;
    }

    /**
     * Validate this
     */
    public function validate()
    {
        $this->result = Model::getValidatorAdapter()->validate($this->getValidatorList(), $this->data);
        $this->isValid = Model::getValidatorAdapter()->isValid($this->result);

        return $this;
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

        $this->addNotEmptyValidatorList($this->requiredFields);

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
        $requiredFields = $this->requiredFields;

        foreach ($data as $field => $value) {
            if (is_null($value) && !in_array($field, $requiredFields)) {
                unset($data[$field]);
            }
        }

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