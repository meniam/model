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
     * @param array $validatorList
     * @param array $data
     * @return mixed
     */
    abstract public function validate($validatorList, array $data);

    /**
     * @param $validatorResult
     * @return bool
     */
    abstract public function isValid($validatorResult);

    /**
     * @return mixed
     */
    abstract public function getNotEmptyValidator();

    /**
     * Get decorated messages array
     *
     * @param array $validationResult
     * @return array
     */
    abstract public function getValidatorMessages($validationResult);
}