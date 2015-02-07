<?php

namespace Model\Validator\Adapter;

/**
 * Interface AdapterInterface
 *
 * @package Model\Validator\Adapter
 */
abstract class AbstractAdapter
{
    /**
     * @param       $class
     * @param array $args
     * @param array $namespaces
     *
     * @return mixed
     */
    abstract public function getValidatorInstance($class, array $args = array(), array $namespaces = array());

    /**
     * @param $validator
     * @param $value
     *
     * @return bool
     */
    abstract public function isValid($validator, $value);

    /**
     * @return mixed
     */
    abstract public function getNotEmptyValidator();

    /**
     * Get decorated messages array
     *
     * array(
        'field1' => array(
            'error_code_1' => 'Message 1'
            'error_code_2' => 'Message 2'
            'error_code_3' => 'Message 3'
        ),
        'field2' => array(
            'error_code_1' => 'Message 1'
            'error_code_2' => 'Message 2'
            'error_code_3' => 'Message 3'
        ),
     * )
     *
     * @param array $validatorList
     * @return array
     */
    abstract public function getValidatorMessages($validatorList);
}