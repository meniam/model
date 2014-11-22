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
class Md5 extends AbstractFilter
{
    public function filter($value)
    {
        return md5($value);
    }
}
