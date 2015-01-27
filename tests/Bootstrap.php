<?php
/**
 * Model Tests Bootstrap
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
// Drop system include path
set_include_path('');

require_once __DIR__ . '/vendor/autoload.php';

if (!is_file(__DIR__ . '/config/config.php')) {
    die("Config not found; copy config/config.sample.php config/config.php");
}
require_once __DIR__ . '/config/config.php';

/**
 * Setup autoloading
 */
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


if (defined('CACHE_DIR') && CACHE_DIR) {
    /** @var Zend\Cache\Storage\Adapter\Filesystem $cache */
    $cache = \Zend\Cache\StorageFactory::factory(array(
        'adapter' => array(
            'name' => 'filesystem',
            'options' => array(
            'cache_dir' => __DIR__ . '/cache'),
            'dir_level' => 3,
            'dir_permission' => 0777,
            'file_permission' => 0666,
            'no_atime' => true
        ),
    ));

    $plugin = new \Zend\Cache\Storage\Plugin\Serializer();
    $cache->addPlugin($plugin);

    \Model\Cluster\Schema::setCacheAdapter($cache);
}

$db = new Model\Db\Mysql('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME .';charset=UTF8', DB_USER, DB_PASSWORD);
ModelTest\TestCase::setDb($db);
