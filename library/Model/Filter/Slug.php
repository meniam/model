<?php

namespace Model\Filter;

class Slug extends Name
{
	public function filter($value)
	{
        $value = parent::filter($value);
        $value = str_replace('&', 'and', $value);

		$value = Filter::filterStatic($value, 'Model\\Filter\\Translit');
        return trim(preg_replace('#[\-\_\s]+#u', '-', $value), ' -');
	}
}
