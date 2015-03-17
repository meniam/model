<?php
/**
 * Created by PhpStorm.
 * User: RuSPanzer
 * Date: 15.02.2015
 * Time: 14:37
 */

namespace Model\Validator\Adapter;


class WithoutValidation extends AbstractAdapter
{

    /**
     * @param       $class
     * @param array $args
     * @param array $namespaces
     *
     * @return mixed
     */
    public function getValidatorInstance($class, array $args = array(), array $namespaces = array())
    {
        return new \stdClass();
    }

    /**
     * @param array $validatorList
     * @param array $data
     * @return mixed
     */
    public function validate($validatorList, array $data)
    {
        return null;
    }

    /**
     * @param $validatorResult
     * @return bool
     */
    public function isValid($validatorResult)
    {
        return true;
    }

    /**
     * @return mixed
     */
    public function getNotEmptyValidator()
    {
        return new \stdClass();
    }

    /**
     * Get decorated messages array
     *
     * @param array $validationResult
     * @return array
     */
    public function getValidatorMessages($validationResult)
    {
        return array();
    }
}