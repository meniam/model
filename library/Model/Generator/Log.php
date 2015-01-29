<?php
/**
 * LICENSE: THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NON INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @category   Generator
 * @package    Model
 * @author     Eugene Myazin <eugene.myazin@gmail.com>
 * @copyright  2008-2015 Eugene Myazin <eugene.myazin@gmail.com>
 * @license    https://github.com/meniam/model/blob/master/MIT-LICENSE.txt  MIT License
 */

namespace Model\Generator;

use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Zend\Log\Exception\RuntimeException;

/**
 * Logger class
 *
 * Class Log
 *
 * @package Model\Generator
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
     * @throws RuntimeException
     * @return void
     */
    public static function __callStatic($method, $params)
    {
        $priority = strtoupper($method);
        if (($priority = array_search($priority, self::_getPriorities())) !== false) {
            switch (count($params)) {
                case 0:
                    /** @see Zend_Log_Exception */
                    throw new RuntimeException('Missing log message');
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
            throw new RuntimeException('Bad log priority');
        }
    }

    /**
     * @param            $message
     * @param int        $priority
     * @param array|null $extras
     *
     * @throws \Zend\Log\Exception\InvalidArgumentException
     * @throws RuntimeException
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

    /**
     * @return array
     */
    protected static function _getPriorities()
    {
    	if (empty(self::$_priorities)) {
    		$r = new \ReflectionClass('Zend\Log\Logger');
        	self::$_priorities = array_flip($r->getConstants());
    	}

    	return self::$_priorities;
    }

    /**
     * @return Logger
     */
	protected static function _getLogAdapter()
	{
		if (empty(self::$_logAdapter)) {
			$writer = new Stream('php://output');
			self::$_logAdapter = new Logger();
            self::$_logAdapter->addWriter($writer);
		}

		return self::$_logAdapter;
	}

    /**
     * @param $adapter
     */
	public static function setLogAdapter($adapter)
	{
		self::$_logAdapter = $adapter;
	}
}