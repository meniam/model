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

define('FIXTURES_PATH',   realpath(__DIR__ . '/_fixtures'));

include __DIR__ . '/autoload.php';

$db = new Model\Db\Mysql('mysql:host=localhost;dbname=model_test;charset=UTF8', 'root', 'macbook');

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
ModelTest\TestCase::setDb($db);
