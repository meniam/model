<?php

namespace Model\Generator;

use Zend\Log\Logger;

/**
 * Логирование действий генератора
 *
 * @method static void info($message)
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 *
 */
class Log
{
	protected static $_logAdapter = null;

	protected static $_priorities = array();

    /**
     * Undefined method handler allows a shortcut:
     *   $log->priorityName('message')
     *     instead of
     *   $log->log('message', Zend_Log::PRIORITY_NAME)
     *
     * @param  string  $method  priority name
     * @param  string  $params  message to log
     * @throws \Zend\Log\Exception\RuntimeException
     * @return void
     */
    public static function __callStatic($method, $params)
    {
        $priority = strtoupper($method);
        if (($priority = array_search($priority, self::_getPriorities())) !== false) {
            switch (count($params)) {
                case 0:
                    /** @see Zend_Log_Exception */
                    throw new \Zend\Log\Exception\RuntimeException('Missing log message');
                case 1:
                    $message = array_shift($params);
                    $extras = array();
                    break;
                default:
                    $message = array_shift($params);
                    $extras  = array_shift($params);
                    break;
            }
            self::_log($message, $priority, $extras);
        } else {
            throw new \Zend\Log\Exception\RuntimeException('Bad log priority');
        }
    }

    /**
     * @param            $message
     * @param int        $priority
     * @param array|null $extras
     *
     * @throws \Zend\Log\Exception\InvalidArgumentException
     * @throws \Zend\Log\Exception\RuntimeException
     * @return Logger
     */
    protected static function _log($message, $priority = Logger::INFO, array $extras = array())
    {
        return self::_getLogAdapter()->log($priority, $message, $extras);
    }

    /**
     * @param $message
     *
     * @return Logger
     */
    public static function debug($message)
    {
        return self::_log($message, Logger::DEBUG);
    }


    protected static function _getPriorities()
    {
    	if (empty(self::$_priorities)) {
    		$r = new \ReflectionClass('Zend\Log\Logger');
        	self::$_priorities = array_flip($r->getConstants());
    	}

    	return self::$_priorities;
    }

	protected static function _getLogAdapter()
	{
		if (empty(self::$_logAdapter)) {
			$writer = new \Zend\Log\Writer\Null();
			self::$_logAdapter = new \Zend\Log\Logger();
            self::$_logAdapter->addWriter($writer);
		}

		return self::$_logAdapter;
	}

	public static function setLogAdapter($adapter)
	{
		self::$_logAdapter = $adapter;
	}
}