<?php

namespace Model\Filter;

use Zend\Filter\AbstractFilter;

class Name extends AbstractFilter
{
	public function filter($value)
	{
        if (!($value = Filter::filterStatic($value, '\\Model\\Filter\\StripText'))) {
            return $value;
        }

        $value = preg_replace('#\s+#usi', ' ', $value);
        return  trim(Filter::filterStatic($value, '\\Model\\Filter\\Truncate',
                    array('length' => 255,
                          'etc' => '',
                          'break_words' => false,
                          'middle' => false)));
	}
}
