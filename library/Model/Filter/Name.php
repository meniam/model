<?php

namespace Model\Filter;

class Name extends \Zend\Filter\AbstractFilter
{
	public function filter($value)
	{
        if (!($value = Filter::filterStatic($value, '\\Model\\Filter\\StripText'))) {
            return $value;
        }

        $value = preg_replace('#\s+#usi', ' ', $value);
        $value = Filter::filterStatic($value, '\\Model\\Filter\\Truncate', array('length' => 255, 'etc' => '', 'break_words' => false, 'middle' => false));

		return trim($value);
	}
}
