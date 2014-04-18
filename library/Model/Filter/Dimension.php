<?php

namespace Model\Filter;

use Zend\Filter\FilterInterface;

class Dimension implements FilterInterface
{
	public function filter($value)
	{
        if (is_int($value) || is_float($value)) {
            return (float)$value;
        }

        if (!($value = Filter::filterStatic($value, 'Model\Filter\StringTrim'))) {
            return (float)$value;
        }

        //@todo Пока прогоняем через price, ибо логика такая же, но нужно сделать парсер mm, m, inch и т.д
        $value = Filter::filterStatic($value, 'Model\Filter\Price');

		return $value;
	}
}
