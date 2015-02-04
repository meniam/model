<?php

namespace Model\Validator;

use Model\Model;

class ValidatorSet
{
    private $validatorList = array();

    private $data = array();

    public function __construct()
    { }

    public function isValid()
    {
        $result = true;

        foreach ($this->data as $field => $value) {
            if (isset($this->validatorList[$field]) && is_array($this->validatorList[$field])) {
                foreach ($this->validatorList[$field] as $validator) {
                    $result = Model::getValidatorAdapter()->isValid($validator, $value);
                    if (!$result) {
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param array $validatorRequiredFields
     * @return $this
     */
    public function addNotEmptyValidatorList(array $validatorRequiredFields)
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
    public function setValidatorList(array $validatorList)
    {
        foreach ($validatorList as $field => $validators) {
            foreach ($validators as $validator) {
                $this->validatorList[$field][] = clone $validator;
            }
        }

        return $this;
    }

    /**
     * @param $name
     * @param $validator
     * @return $this
     */
    public function addValidator($name, $validator)
    {
        $this->validatorList[$name][] = clone $validator;
        return $this;
    }

    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }
}