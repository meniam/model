<?php

namespace Model\Filter;

/**
 * Превращает строку в SHA1
 *
 * @category   category
 * @package    package
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Sha1 extends \Zend\Filter\AbstractFilter
{
    public function filter($value)
    {
        return sha1($value);
    }
}
