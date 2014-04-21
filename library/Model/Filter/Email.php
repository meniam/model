<?php

namespace Model\Filter;

use Zend\Filter\AbstractFilter;

/**
 * Превращает строку в MD5
 *
 * @category   category
 * @package    package
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Email extends AbstractFilter
{
    public function filter($value)
    {
        $value = strip_tags($value);
        if (substr($value, 0, 7) == 'mailto:') {
            $value = substr($value, 7);
        }

        $value = preg_replace('#[\[\(]\s*(@|at)\s*[\]\)]#usi', '@', $value);

        return preg_replace('#[^0-9a-zA-Z\.\@\-\_]+#usi', '', $value);
    }
}
