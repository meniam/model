<?php

namespace Model\Filter;

use Zend\Filter\FilterInterface;

class Ucwords implements FilterInterface
{
    public function filter($value)
    {
        $value = Filter::filterStatic($value, 'Model\Filter\StringTrim');

        if (empty($value)) {
            return $value;
        }

        $value = preg_replace('#\s+#', ' ', $value);
        $value = mb_convert_case($value, MB_CASE_TITLE, "UTF-8");
        return $value;
	}
}

