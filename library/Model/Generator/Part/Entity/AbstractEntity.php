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
 * @category   Collection
 * @package    Model
 * @author     Eugene Myazin <eugene.myazin@gmail.com>
 * @copyright  2008-2013 Eugene Myazin <eugene.myazin@gmail.com>
 * @license    https://github.com/meniam/model/blob/master/MIT-LICENSE.txt  MIT License
 */

namespace Model\Generator\Part\Entity;

use Model\Generator\Part\AbstractPart  as AbstractPart;
use Model\Generator\Part\PartInterface as PartInterface;
use Model\Generator\Log                as Log;
use Model\Cluster\Schema;
use Model\Cluster\Schema\Table;

class AbstractEntity extends AbstractPart implements PartInterface
{
  	public function __construct(Table $table, Schema $cluster, $outputFilename = null)
	{
		Log::info('Generate part entity ' . $table->getName());
        $this->_table = $table;

        $file  = new \Model\Code\Generator\FileGenerator();
        $class = new  \Zend\Code\Generator\ClassGenerator();
        $file->setClass($class);
        $this->setFile($file);

		$this->_runPlugins(self::PART_ENTITY_ABSTRACT, self::RUNTIME_PRE);

        $class->setName($table->getNameAsCamelCase() . 'EntityAbstract');
        $class->setExtendedClass('\Model\Entity');

		$this->_runPlugins(self::PART_ENTITY_ABSTRACT, self::RUNTIME_POST);

        if ($outputFilename) {
            file_put_contents($outputFilename, $file->generate());
        }
    }
}
