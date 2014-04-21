<?php

namespace Model\Filter;

use Zend\Filter\AbstractFilter;

class Float extends AbstractFilter
{
	/**
	 * Defined by Zend_Filter_Interface
	 *
	 * Returns (float) $value
	 *
	 * @param  string $value
	 * @return float
	 */
	public function filter($value)
	{
		return (float) ((string) $value);
	}
}
