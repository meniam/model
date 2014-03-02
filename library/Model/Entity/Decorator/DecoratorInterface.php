<?php

namespace Model\Entity\Decorator;

interface DecoratorInterface
{
	public function __construct($input);
	public function  __toString();
}
