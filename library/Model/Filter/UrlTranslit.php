<?php

namespace Model\Filter;

class UrlTranslit extends Translit
{
	public function filter($value)
	{
		return parent::url($value);
	}
}
