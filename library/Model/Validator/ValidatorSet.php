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
    private $validateResult = null;

    public function __construct($validatorList, $data, $requiredFields)
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
        if (!is_null($this->validateResult)) {
            return $this->validateResult;
        }

        $result = true;
        foreach ($this->data as $field => $value) {
            if (isset($this->validatorList[$field]) && is_array($this->validatorList[$field])) {
                foreach ($this->validatorList[$field] as $validator) {
                    $result = (bool)Model::getValidatorAdapter()->isValid($validator, $value);
                    if (!$result) {
                        break;
                    }
                }
            }
        }

        $this->validateResult = $result;
        return $result;
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
        return Model::getValidatorAdapter()->getValidatorMessages($this->validatorList);
    }
}