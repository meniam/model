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
}