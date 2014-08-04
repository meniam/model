<?php

namespace Model\Filter;

use Model\Filter\Exception\InvalidArgumentException;
use Zend\Filter\AbstractFilter;

/**
 * Фильтрация данных
 *
 * @category   category
 * @package    package
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Filter
{
    /**
     * @var array
     */
    protected static $_filterInstance = array();

    /**
     *
     * @param  mixed        $value
     * @param               $class
     * @param  array        $args          OPTIONAL
     * @param  array|string $namespaces    OPTIONAL
     * @internal param string $classBaseName
     * @return mixed
     */
    public static function filterStatic($value, $class, array $args = array(), $namespaces = array())
    {
        /** @var $filterObject AbstractFilter */
        $filterObject = self::getFilterInstance($class, $args, $namespaces);
        return $filterObject->filter($value);
    }

    /**
     * @param       $class
     * @param array $args
     * @param array $namespaces
     * @throws \Model\Filter\Exception\InvalidArgumentException
     * @return mixed
     */
    public static function getFilterInstance($class, array $args = array(), array $namespaces = array())
    {
        $namespaces = array_merge($namespaces, array('', '\\Model\\Filter', '\\Zend\\Filter'));

        $argsHash = empty($args) ? '' : md5(serialize($args));

        foreach ($namespaces as $namespace) {
            $className = $namespace ? (rtrim($namespace, '\\') . '\\' . ucfirst($class)) : $class;

            if (isset(self::$_filterInstance[$className . '_' . $argsHash])) {
                return self::$_filterInstance[$className . '_' . $argsHash];
            }

            if (class_exists($className)) {
                $_class = new \ReflectionClass($className);

                if ($_class->implementsInterface('\Zend\Filter\FilterInterface')) {
                    if ($_class->hasMethod('__construct')) {
                        $object = $_class->newInstanceArgs(array($args));
                    } else {
                        $object = $_class->newInstance();
                    }

                    self::$_filterInstance[$className . '_' . $argsHash] = $object;

                    return $object;
                }
            }
        }

        throw new InvalidArgumentException("Filter class not found");
    }
}