<?php

namespace Model\Filter;

class Ucwords extends AbstractFilter
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

