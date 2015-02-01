<?php

namespace Model\Validator\Adapter;

/**
 * Interface AdapterInterface
 *
 * @package Model\Validator\Adapter
 */
interface AdapterInterface
{
    public static function validatorStatic($value, $class, array $args = array(), $namespaces = array());
    public static function getValidatorInstance($class, array $args = array(), array $namespaces = array());
    public static function validate($validator, $value);
}