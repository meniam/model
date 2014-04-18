<?php
namespace Model\Filter;

class Truncate40 extends Truncate
{
	public function filter($value)
	{
        $this->setLength(40);
		return parent::filter($value);
	}
}
