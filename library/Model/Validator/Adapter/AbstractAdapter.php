<?php

namespace Model\Validator\Adapter;

/**
 * Валидация данных
 *
 * @category   Model
 * @package    Validator
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
abstract class AbstractAdapter
{
    public abstract static function validatorStatic($value, $class, array $args = array(), $namespaces = array());
    public abstract static function getValidatorInstance($class, array $args = array(), array $namespaces = array());
}