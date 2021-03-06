<?php

namespace Model\Filter;

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
