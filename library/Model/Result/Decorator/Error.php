<?php

namespace Model\Result\Decorator;

use Zend\InputFilter\InputFilter;

class Error
{
    private $errors = array();

    /**
     * Обернуть в декоратор массив ошибок вида:
     * ├ field
     *   └ code => message
     *
     * @param array|Model_Result|Zend_Filter_Input $errors
     */
    public function __construct($errors)
    {
        if ($errors instanceof \Model\Result\Result) {
            $errors = $errors->getErrors();
        }

        if ($errors instanceof InputFilter || $errors instanceof \App\Form) {
            $errors = $errors->getMessages();
        }

        if (!is_array($errors) || empty($errors)) {
            return;
        }

        /**
         * Перелопатим и сложим всё правильно. Лучше перебдеть :)
         */

        $_errors = array();
        foreach ($errors as $field => &$messages) {
            if (!is_array($messages)) {
                continue;
            }
            foreach ($messages as $code => &$message) {
                if (!is_scalar($code) || !is_scalar($message)) {
                    continue;
                }
                $_errors[] = array('code'    => $code,
                                   'message' => trim($message),
                                   'field'   => $field);
            }
        }

        $this->errors = $_errors;
    }

    public function exists()
    {
        return (bool)$this->errors;
    }

    /**
     * Проверить существование ошибки по коду
     * @param string $code
     * @return bool
     */
    public function hasErrorByCode($code)
    {
        foreach ($this->errors as $error) {
            if ($error['code'] == $code) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $fieldName
     * @return array
     */
    public function getMessagesByFieldName($fieldName)
    {
        $result = array();

        foreach ($this->errors as &$error) {
            if ($error['field'] == $fieldName) {
                $result[$error['code']] = $error['message'];
            }
        }

        return $result;
    }

    /**
     * @param $fieldName
     * @return array
     */
    public function getMessagesGroupByFields()
    {
        $result = array();

        foreach ($this->errors as &$error) {
            $result[$error['field']][$error['code']] = $error['message'];
        }

        return $result;
    }

    /**
     * Получить как есть:
     * ├ index
     *   ├ field
     *   ├ code
     *   └ message
     *
     * @return array
     */
    public function toArray()
    {
        return $this->errors;
    }

    /**
     * Получить как строку в виде:
     * message[field](code), ...
     *
     * @param string $separator
     * @return string
     */
    public function toLogString($separator = "\n")
    {
        if (!$this->exists()) {
            return "";
        }

        $result = array();

        foreach ($this->toArray() as $_message) {
            $message = @$_message['message'];
            $field = @$_message['field'];
            $code = @$_message['code'];
            $result[] = $message . '[' . $field . '](' . $code . ')';
        }

        return implode($separator, $result);
    }

    /**
     * Получить как строку в виде:
     * Code: message, ...
     *
     * @param string $separator
     * @return string
     */
    public function toString($separator = "\n")
    {
        if (!$this->exists()) {
            return "";
        }

        $result = array();

        foreach ($this->toArray() as $_message) {
            $message = @$_message['message'];
            $field = @$_message['field'];
            $code = @$_message['code'];
            $parts = explode('__', $field);
            $field = implode('', array_map('ucfirst', explode('_', end($parts))));
            $result[] = $field . ': ' . ($message ? : $code);
        }

        return implode($separator, $result);
    }

    public function __toString()
    {
        return $this->toString();
    }
}