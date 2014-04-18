<?php

namespace Model\Filter;

class TagName extends Name
{
	public function filter($value)
	{
		$value = parent::filter($value);

		$value = preg_replace('~&#x*([0-9a-f]+);~ei', ' ', $value);
		$value = preg_replace('~&#*([0-9]+);~e', ' ', $value);

		$value = preg_replace('#\(.*?\)#usi', '', $value);
		$value = str_replace(array('\\', '\/'), '', $value);

		$value = preg_replace('#[^\-\/0-9a-zA-ZА-Яа-яЕЁеёЫы\&\'\"]+#usi', ' ', $value);

		$value = preg_replace('#\s+#usi', ' ', $value);
		return trim(mb_strtolower($value, 'UTF-8'));
	}
}
