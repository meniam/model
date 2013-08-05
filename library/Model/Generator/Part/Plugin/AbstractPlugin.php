<?php

namespace Model\Generator\Part\Plugin;

use Model\Generator\Part\Plugin\PluginInterface;
use \Model\Generator\Part\PartInterface;

abstract class AbstractPlugin implements PluginInterface
{
	protected $_name;

	public function __construct()
	{}

	protected function _setName($name)
	{
		$this->_name = (string)$name;
	}

	public function getName()
	{
		return $this->_name;
	}

	public function preRun(PartInterface $part)
	{}

	public function postRun(PartInterface $part)
	{}
}