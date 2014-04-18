<?php

namespace Model\Filter;

/**
 * Превращает Entity в симоволы
 */
class EntityDecode extends \Zend\Filter\AbstractFilter
{
    static public $_entity = array(
        '&nbsp;'    => ' ',
        '&laquo;'   => '"',
        '&raquo;'   => '"',
        '&quot;'    => '"',
        '&#150;'    => '-',
        '&#151;'    => '-',
        '&#39;'     => "'",
        '&amp;'     => "&",
        '&apos;'    => "'",
        '&lt;'      => "<",
        '&gt;'      => ">",
        '«'         => '"',
        '»'         => '"',
        '„'         => '"',
        '“'         => '"',
        '—'         => '-',
        '’'         => "'",
        '…'         => '...'
    );

    public function filter($value)
    {
        $value = (string)$value;
        $value = str_replace(array_keys(self::$_entity), array_values(self::$_entity), $value);
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        return $value;
    }
}
