<?php

namespace Model\Validator;

use Zend\Validator\AbstractValidator;

class ValidatorSet
{

    /**
     * @param         $name
     * @param boolean $allowEmpty
     *
     * @return $this
     */
    public function setAllowEmpty($name, $allowEmpty)
    {
        $this->data[$name]['allow_empty'] = (bool)$allowEmpty;
        return $this;
    }

    /**
     * @param $name
     *
     * @return boolean
     */
    public function getAllowEmpty($name)
    {
        return isset($this->data[$name]['allow_empty']) ? $this->data[$name]['allow_empty'] : false;
    }

    /**
     * @param         $name
     * @param boolean $continueIfEmpty
     * @return $this
     */
    public function setContinueIfEmpty($name, $continueIfEmpty)
    {
        $this->data[$name]['continue_if_empty'] = (bool)$continueIfEmpty;
        return $this;
    }

    /**
     * @param $name
     * @return boolean
     */
    public function getContinueIfEmpty($name)
    {
        return isset($this->data[$name]['continue_if_empty']) ? $this->data[$name]['continue_if_empty'] : false;
    }

    /**
     * @param         $name
     * @param         $required
     *
     * @internal param bool $continueIfEmpty
     * @return $this
     */
    public function setRequired($name, $required)
    {
        $this->data[$name]['required'] = (bool)$required;
        return $this;
    }

    /**
     * @param $name
     * @return boolean
     */
    public function getRequired($name)
    {
        return isset($this->data[$name]['required']) ? $this->data[$name]['required'] : false;
    }


    public function setValue($name, $value)
    {
        $this->rawValues[$name] = $value;

        if (isset($this->data[$name])) {
            $this->data[$name]['value'] = $value;
        }
        return $this;
    }


    public function setValueList(array $valueList)
    {
        foreach ($valueList as $name => $value) {
            $this->setValue($name, $value);
        }
        return $this;
    }


    public function setData(array $data)
    {
        return $this->setValueList($data);
    }

    protected $messageList = array();
    protected $data = array();

    /**
     * @param mixed $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->data[$name]['name'] = $name;
        return $this;
    }

    /**
     * @param $name
     * @return bool
     */
    public function getName($name)
    {
        return isset($this->data[$name]['name']) ? $this->data[$name]['name'] : false;
    }

    public function setMessageList(array $messageList)
    {
        $this->messageList = $messageList;
        return $this;
    }

    /**
     * @return array
     */
    public function getMessageList()
    {
        return $this->messageList;
    }

    public function addMessage($field, $message, $code)
    {
        $this->messageList[$field][$code] = $message;
        return $this;
    }

    public function setValidatorList(array $validatorList)
    {
        $this->validatorList = $validatorList;
        return $this;
    }

    public function getValidatorList()
    {
        return $this->validatorList;
    }

    public function addValidatorList($name, array $validatorList)
    {
        foreach ($validatorList as $validator) {
            $this->addValidator($name, $validator);
        }

        return $this;
    }

    public function addValidator($name, $validator)
    {
        $this->data[$name]['validators'][] = $validator;
        return $this;
    }


    public function __construct()
    { }

    public static function create($inputSpecification) {
        if (!is_array($inputSpecification)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable; received "%s"',
                __METHOD__,
                (is_object($inputSpecification) ? get_class($inputSpecification) : gettype($inputSpecification))
            ));
        }

        $validatorSet = new self();

        foreach ($inputSpecification as $key => $validatorParamArray) {
            $name = $key;
            $validatorSet->setName($key, $key);
            foreach ($validatorParamArray as $key => $value) {
                switch($key) {
                    case 'validators':
                        if (isset($value)) {
                            $validatorSet->addValidatorList($name, $value);
                        }
                        break;
                    case 'required':
                        $validatorSet->setRequired($name, $value);
                        if (isset($inputSpecification['allow_empty'])) {
                            $validatorSet->setAllowEmpty($name, $inputSpecification['allow_empty']);
                        }
                        break;
                    case 'allow_empty':
                        $validatorSet->setAllowEmpty($name, $value);
                        break;
                    case 'continue_if_empty':
                        $validatorSet->setContinueIfEmpty($name, $value);
                        break;

                }
            }
        }

        return $validatorSet;
    }


    public function isValid($field = null)
    {
        if ($field == null) {
            foreach ($this->data as $field => $validatorParamArray) {
                $value = isset($validatorParamArray['value']) ? $validatorParamArray['value'] : null;

                // Если значения нет и это нормально продолжаем
                if (empty($value) && !$this->getRequired($field)) {
                    continue;
                }

                if (empty($value) && $this->getRequired($field)) {
                    $this->addMessage($field, 'Element can not be empty', 'Empty');
                    return false;
                }

                if (isset($validatorParamArray['validators']) && is_array($validatorParamArray['validators'])) {
                    foreach ($validatorParamArray['validators'] as $validator) {
                        if (!$validator->isValid($value)) {
                            foreach ($validator->getMessages() as $code => $message) {
                                $this->addMessage($field, $message, $code);
                            }
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    public function getMessages()
    {
        $result = array();
        foreach ($this->messageList as $messageList) {
            foreach ($messageList as $code => $message) {
                $result[$code] = $message;
            }
        }

        return $result;
    }

}