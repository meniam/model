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
class EnumField extends AbstractFilter
{
    public function filter($value)
    {
        return trim(preg_replace('#[^a-z0-9\_]+#usi', '', $value), '_');
    }
}
