<?php

namespace Model\Stdlib;

/**
 * Utility class for testing and manipulation of PHP arrays.
 *
 * Declared abstract, as we have no need for instantiation.
 */
abstract class ArrayUtils extends \Zend\Stdlib\ArrayUtils
{
    public static function filterEmpty($input)
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = self::filterEmpty($value);
            }
        }

        return array_filter($input);
    }

    public static function recursiveKeySort(&$array)
    {
        ksort($array);
        foreach(array_keys($array) as $k)
        {
            if (is_array($array[$k])) {
                self::recursiveKeySort($array[$k]);
            }
        }
    }

    public static function recursiveSmartSort(&$array)
    {
        is_int(key($array)) ? asort($array) : ksort($array);

        foreach(array_keys($array) as $k) {
            if (is_array($array[$k])) {
                self::recursiveSmartSort($array[$k]);
            }
        }
    }

    /**
     * Хеш массива
     *
     * @param array $arr
     * @param string $hashMethod
     * @return string
     */
    static public function hash($arr, $hashMethod = 'sha1')
    {
        if (!is_array($arr)) {
            return null;
        }

        self::recursiveSmartSort($arr);
        return $hashMethod(serialize($arr));
    }

}
