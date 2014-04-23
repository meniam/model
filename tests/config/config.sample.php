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
 * @category   Config
 * @package    ModelTest
 * @author     Eugene Myazin <eugene.myazin@gmail.com>
 * @copyright  2008-20014 Eugene Myazin <eugene.myazin@gmail.com>
 * @license    https://github.com/meniam/model/blob/master/MIT-LICENSE.txt  MIT License
 */

define('DB_HOST',     'models-db-server');
define('DB_USER',     'macbook');
define('DB_PASSWORD', 'test');
define('DB_NAME',     'model_test');

define('FIXTURES_PATH',   realpath(__DIR__ . '/../_fixtures'));

defined('PROJECT_PATH')    || define('PROJECT_PATH', realpath(__DIR__ . '/../'));

$cachedir = get_temp_dir() . '/ModelTestCache';

if (!is_dir($cachedir)) {
    @mkdir($cachedir, 0777, true);
}

define('CACHE_DIR', is_dir($cachedir) ? $cachedir : false);

$generateOutput = get_temp_dir() . '/ModelTestGeneratedModels';

if (!is_dir($generateOutput)) {
    @mkdir($generateOutput, 0777, true);
}

defined('GENERATE_OUTPUT') || define('GENERATE_OUTPUT', is_dir($generateOutput) ? $generateOutput : false);


function get_temp_dir() {
    if (!empty($_ENV['TMP'])) {
        return realpath($_ENV['TMP']);
    }

    if (!empty($_ENV['TMPDIR'])) {
        return realpath( $_ENV['TMPDIR']);
    }

    if (!empty($_ENV['TEMP'])) {
        return realpath( $_ENV['TEMP']);
    }

    $tempfile=tempnam(__FILE__,'');

    if (file_exists($tempfile)) {
        unlink($tempfile);
        return realpath(dirname($tempfile));
    }

    return null;
}