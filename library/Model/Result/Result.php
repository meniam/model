<?php

namespace Model\Result;

use Model\Exception\ErrorException;
use Model\Result\Decorator\Error;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;

/**
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
     * @var InputFilterInterface
     */
    private $validator;

    /**
     * Вложенные результаты
     *
     * @var Result[]
     */
    protected $childs = array();

    /**
     * @param mixed $result Результат действия, например айдишник
     * @param InputFilterInterface $validator
     * @return Result
     */
    public function __construct($result = null, InputFilterInterface $validator = null)
    {
        $this->setResult($result);

        if ($validator !== null) {
            $this->setValidator($validator);
        }
    }

    /**
     * @param $result
     * @return Result
     */
    public function setResult($result)
    {
        $this->result = $result;
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
     * @param InputFilterInterface $validator
     * @return Result
     */
    public function setValidator(InputFilterInterface $validator)
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Получить объект валидатора
     *
     * @return InputFilterInterface
     */
    public function getValidator()
    {
        if (!$this->validator) {
            $this->validator = new InputFilter();
            $this->validator->setData(array());
        }

        return $this->validator;
    }

    /**
     * Добавить дочерний результат
     *
     * @param string             $name
     * @param Result|InputFilter $child
     * @throws ErrorException
     * @return Result
     */
    public function addChild($name, $child)
    {
        if ($child instanceof InputFilter) {
            $child = new Result(null, $child);
        } else if (!$child instanceof Result) {
            throw new ErrorException('Child must be instance of \Model\Result\Result or InputFilter');
        }

        if (!is_scalar($name)) {
            throw new ErrorException('Child name must be a string');
        }

        $i = 0;
        while (array_key_exists($name . '.' . $i, $this->childs)) {
            $i++;
        }

        $this->childs[$name . '.' . $i] = $child;
        return $this;
    }

    /**
     * Верны ли данные
     *
     * @param string $field
     * @return boolean
     */
    public function isValid($field = null)
    {
        $result = $this->getValidator()->isValid($field);

        if ($result && $field === null) {
            foreach ($this->childs as &$childResult) {
                if (!$result = $childResult->isValid()) {
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Получить массив или декоратор ошибок
     * @param bool $decorate
     * @throws ErrorException
     * @return mixed|Error
     */
    public function getErrors($decorate = false)
    {
        $errors = $this->getValidator()->getMessages();

        foreach ($this->childs as $name => &$childResult) {
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