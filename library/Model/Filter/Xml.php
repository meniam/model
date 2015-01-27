<?php
/**
 * Фильтр запрещенных символов, которые рушат xml
 */
namespace Model\Filter;

class Xml extends AbstractFilter
{
	public function filter($value)
	{
        return preg_replace("#[\x01-\x08\x0B-\x0C\x0E-\x1F]#","", $value);
	}
}
