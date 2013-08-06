<?php
/**
 * Model Tests Autoload
 *
 * LICENSE: THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NON INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @category   Bootstrap
 * @package    ModelTest
 * @author     Eugene Myazin <eugene.myazin@gmail.com>
 * @copyright  2008-20013 Eugene Myazin <eugene.myazin@gmail.com>
 * @license    https://github.com/meniam/model/blob/master/MIT-LICENSE.txt  MIT License
 */

defined('PROJECT_PATH')    || define('PROJECT_PATH', realpath(__DIR__ . '/../'));
defined('GENERATE_OUTPUT') || define('GENERATE_OUTPUT', '/tmp/generate');

// Drop system include path
set_include_path('');

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Setup autoloading
 */
require_once 'Zend/Loader/StandardAutoloader.php';
$loader = new \Zend\Loader\StandardAutoloader(
    array(
        'autoregister_zf' => true,
         Zend\Loader\StandardAutoloader::LOAD_NS => array(
             'Model'     => __DIR__ . '/../library/Model',
             'ModelTest' => __DIR__ . '/ModelTest',
         ),
    ));
$loader->register();

if (is_file(GENERATE_OUTPUT . '/_autoload_classmap.php')) {
    $a = require(GENERATE_OUTPUT . '/_autoload_classmap.php');
    $loader = new \Zend\Loader\ClassMapAutoloader(array($a));
    $loader->register();
}
