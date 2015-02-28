<?php

namespace Model\Stdlib;

/**
 * Utility class for testing and manipulation of PHP arrays.
 *
 * Declared abstract, as we have no need for instantiation.
 */
abstract class ArrayUtils
{

    /**
     * Merge two arrays together.
     *
     * If an integer key exists in both arrays and preserveNumericKeys is false, the value
     * from the second array will be appended to the first array. If both values are arrays, they
     * are merged together, else the value of the second array overwrites the one of the first array.
     *
     * @param  array $a
     * @param  array $b
     * @param  bool  $preserveNumericKeys
     * @return array
     */
    public static function merge(array $a, array $b, $preserveNumericKeys = false)
    {
        foreach ($b as $key => $value) {
            if (array_key_exists($key, $a)) {
                if (is_int($key) && !$preserveNumericKeys) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = static::merge($a[$key], $value, $preserveNumericKeys);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }


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

    /**
     * @param array $data
     * @param array $original
     * @return array
     */
    public static function filterByOriginalArray(array $data, array $original)
    {
        $result = array();
        foreach ($data as $key => $value) {
            if (in_array($key, $original)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

}
