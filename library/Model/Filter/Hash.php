<?php

namespace Model\Filter;

class Hash extends EntityDecode
{
	public function filter($value)
	{
		$value = mb_strtolower(parent::filter($value) ,'UTF-8');
		$value = preg_replace('#[^A-F\d]+#si', '', $value);
		return mb_substr($value, 0, 40, 'UTF-8');
	}
}
