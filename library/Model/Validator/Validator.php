<?php

namespace Model\Validator;

use Model\Validator\Exception\ErrorException;

/**
 * Валидация данных
 *
 * @category   App
 * @package    Validator
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Validator
{
    /**
     * @var array
     */
    protected static $validatorInstance = array();

    /**
     *
     * @param  mixed        $value
     * @param               $class
     * @param  array        $args          OPTIONAL
     * @param  array|string $namespaces    OPTIONAL
     * @internal param string $classBaseName
     * @return mixed
     */
    public static function validatorStatic($value, $class, array $args = array(), $namespaces = array())
    {
        /** @var $validateObject \Zend\Validator\AbstractValidator */
        $validateObject = self::getValidatorInstance($class, $args, $namespaces);
        return $validateObject->isValid($value);
    }

    /**
     * @param       $class
     * @param array $args
     * @param array $namespaces
     *
     * @throws Exception\ErrorException
     */
    public static function getValidatorInstance($class, array $args = array(), array $namespaces = array())
    {
        if (empty($args) && empty($namespaces)) {
            if (isset(self::$validatorInstance[$class])) {
                return self::$validatorInstance[$class];
            }
            if (class_exists($class)) {
                self::$validatorInstance[$class] = new $class();
                return self::$validatorInstance[$class];
            }
        }

        $namespaces = array_merge($namespaces, array('', '\\Zend\\Validator'));

        foreach ($namespaces as $namespace) {
            if ($namespace) {
                $className = rtrim($namespace, '\\') . '\\' . ucfirst($class);
            } else {
                $className  = $class;
            }

            if (!empty($args)) {
                $argsHash = sha1(serialize($args));

                if (isset(self::$validatorInstance[$className . '_' . $argsHash])) {
                    return self::$validatorInstance[$className . '_' . $argsHash];
                }
            } elseif (isset(self::$validatorInstance[$className])) {
                return self::$validatorInstance[$className];
            }

            $_class = new \ReflectionClass($className);
            if ($_class->implementsInterface('\Zend\Validator\ValidatorInterface')) {
                if ($_class->hasMethod('__construct')) {
                    $object = $_class->newInstanceArgs(array($args));
                } else {
                    $object = $_class->newInstance();
                }

                if (!isset($argsHash)) {
                    $argsHash = sha1(serialize($args));
                }

                if (empty($args)) {
                    self::$validatorInstance[$className] = $object;
                } else {
                    self::$validatorInstance[$className . '_' . $argsHash] = $object;
                }

                return $object;
            }
        }

        throw new ErrorException("Validator class not found from basename '{$class}'");
    }
}