<?php

namespace Model\Filter;

/**
 * Превращает строку в MD5
 *
 * @category   category
 * @package    package
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Date extends \Zend\Filter\AbstractFilter
{
    public function filter($value)
    {
        $format = 'Y-m-d H:i:s';
        if (preg_match('#^\d+$#', $value)) {
            $value = date($format, (int)$value);
        } else{
            if (is_scalar($value)) {
                $value = date($format, strtotime($value));
            } else {
                $value = date($format);
            }
        }

        return $value;
    }
}
