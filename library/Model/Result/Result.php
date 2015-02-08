<?php

namespace Model\Result;

use Model\Exception\ErrorException;
use Model\Result\Decorator\Error;
use Model\Validator\ValidatorSet;

/**
 * Result содержит
 * - значение результата
 * - валидатор в виде ValidatorSet
 * - детей
 *
 * Description
 *
 * @category   Model
 * @package    Result
 * @author     Eugene Myazin <meniam@gmail.com>
 * @since      29.12.12 17:27
 * @copyright  2008-2012 esteIT
 * @version    SVN: $Id$
 */
class Result
{
    private $result;

    /**
     * @var array
     */
    protected $errorList = array();

    /**
     * Вложенные результаты
     *
     * @var Result[]
     */
    protected $childList = array();

    /**
     * @var ValidatorSet
     */
    protected $validator;


    /**
     * @param mixed $result Результат действия, например айдишник
     * @return Result
     */
    public function __construct($result = null)
    {
        $this->setResult($result);
    }

    /**
     * @param $result
     * @return Result
     */
    public function setResult($result)
    {
        if ($result instanceof Result) {
            $this->result = $result->getResult();

            $this->addErrorList($result->getErrorList());
            $this->addChildList($result->getChildList());
            $this->setValidator($result->getValidator());
        } else {
            $this->result = $result;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Добавить дочерний результат
     *
     * @param string             $name
     * @param Result|ValidatorSet $child
     * @throws ErrorException
     * @return Result
     */
    public function addChild($name, $child)
    {
        if (!$child instanceof Result) {
            throw new ErrorException('Child must be instance of \Model\Result\Result');
        }

        if (!is_scalar($name)) {
            throw new ErrorException('Child name must be a string');
        }

        $i = 0;
        while (array_key_exists($name . '.' . $i, $this->childList)) {
            $i++;
        }

        $this->childList[$name . '.' . $i] = $child;

        return $this;
    }

    /**
     *
     * @param array|Result[] $childList
     * @return $this
     */
    public function addChildList(array $childList = array())
    {
        foreach ($childList as $n => $v) {
            $this->addChild($n, $v);
        }
        return $this;
    }

    /**
     * @param $name
     *
     * @return array|bool|Result|Result[]
     */
    public function getChild($name)
    {
        if ($name == null) {
            return $this->getChildList();
        } elseif (isset($this->childList[$name])) {
            return $this->childList[$name];
        }

        return false;
    }

    /**
     * @return array|Result[]
     */
    public function getChildList()
    {
        return (array)$this->childList;
    }

    /**
     * @param        $message
     * @param string $code
     * @param string $field
     *
     * @return $this
     */
    public function addError($message, $code = 'general', $field = 'global')
    {
        $this->errorList[(string) $field][(string) $code] = (string)$message;
        return $this;
    }

    /**
     * @param array $errorList
     *
     * @return $this
     */
    public function addErrorList(array $errorList)
    {
        foreach ($errorList as $field => $messageList) {
            foreach ($messageList as $code => $message) {
                $this->addError($message, $code, $field);
            }
        }

        return $this;
    }

    /**
     * Получить массив или декоратор ошибок
     * @param bool $decorate
     * @throws ErrorException
     * @return mixed|Error
     */
    public function getErrors($decorate = false)
    {
        $errors = $this->getErrorList();

        foreach ($this->childList as $name => &$childResult) {
            $_errors = $childResult->getErrors();
            foreach ($_errors as $field => &$error) {
                $errors[$name . '__' . $field] = $error;
            }
        }

        if ($decorate) {
            $count = count($errors);
            $errors = new Error($errors);
            if ($count && !$errors->exists()) {
                throw new ErrorException('Model_Decorator_Error is broken.');
            }
        }

        return $errors;
    }

    /**
     * @return array
     */
    public function getErrorList()
    {
        return $this->errorList;
    }

    /**
     * @param ValidatorSet $validatorSet
     *
     * @return $this
     */
    public function setValidator(ValidatorSet $validatorSet)
    {
        $this->validator = $validatorSet;

        if (!$validatorSet->isValid()) {
            $this->addError('Input data is not valid. See validation result in this validator set');
        }

        return $this;
    }

    /**
     * @return ValidatorSet
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Верны ли данные
     *
     * @return boolean
     */
    public function isValid()
    {
        $result = !$this->isError();

        if ($result) {
            foreach ($this->childList as &$childResult) {
                if (!$result = $childResult->isValid()) {
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * А был ли мальчик?
     *
     * @return boolean
     */
    public function isError()
    {
        $errors = $this->getErrors();
        return !empty($errors);
    }
}