<?php

namespace Model\Entity\Decorator;

use Model\Entity\EntityInterface;

interface DecoratorInterface
{
	public function __construct($input = null, EntityInterface $entity = null);
	public function  __toString();
}
