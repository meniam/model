<?php

namespace Model;

abstract class Singleton
{
	/**
	 * Collection of instances
	 *
	 * @var array
	 */
	private static $_instances = array();

	/**
	 * Private constructor
	 */
	protected function __construct ()
	{}

	/**
	 * Get instance of class
     * 
     * @return $this
	 */
	public static function getInstance()
	{
		// Get name of current class
		$sClassName = get_called_class();

		// Create new instance if necessary
		if (! isset(self::$_instances[$sClassName])) {
			self::$_instances[$sClassName] = new $sClassName();
		}

		return self::$_instances[$sClassName];
	}

	/**
	 * Private final clone method
	 */
	final private function __clone ()
	{}
}
