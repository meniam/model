<?php

namespace Model\Filter;

class StripText extends PlainText
{
	public function filter($value)
	{
		$value = parent::filter($value);
		$value = trim(preg_replace('#\s+#usi', ' ', $value));

		return $value;
	}
}
