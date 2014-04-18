<?php

namespace Model\Filter;

use Zend\Filter\AbstractFilter;

class Truncate extends AbstractFilter
{
    protected static $_encoding = 'UTF-8';

    public function __construct($options = array())
    {
        $this->setOptions($options);
    }

    public function setLength($length)
    {
        $this->options['length'] = (int)$length;
    }

    public function setEtc($etc)
    {
        $this->options['etc'] = (string)$etc;
    }

    public function setBreakWords($flag)
    {
        $this->options['break_words'] = (bool) $flag;
    }

    public function setMiddle($flag)
    {
        $this->options['middle'] = (bool) $flag;
    }

    public function filter($string)
    {
        //$length = 80, $etc = '', $break_words = false, $middle = false
        $length = isset($this->options['length']) ? $this->options['length'] : 255;
        $etc = isset($this->options['etc']) ? $this->options['etc'] : '';
        $break_words = isset($this->options['break_words']) ? $this->options['break_words'] : false;
        $middle = isset($this->options['middle']) ? $this->options['middle'] : false;

        $string = trim(strip_tags($string));

        if ($length == 0)
            return '';

        if (mb_strlen($string) > $length) {
            $length -= min($length, mb_strlen($etc, self::$_encoding));
            if (!$break_words && !$middle) {
                $string = trim(preg_replace('/\s+?(\S+)?$/', '', mb_substr($string, 0, $length + 1, self::$_encoding)), ' -;.,"\'\(');
            }

            if (!$middle) {
                return mb_substr($string, 0, $length, self::$_encoding) . $etc;
            } else {
                return mb_substr($string, 0, $length / 2, self::$_encoding) . $etc . mb_substr($string, - $length / 2, null, self::$_encoding);
            }
        } else {
            return $string;
        }
    }
}

