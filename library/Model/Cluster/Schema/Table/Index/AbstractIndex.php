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

namespace Model\Cluster\Schema\Table\Index;

use Model\Cluster\Schema as Schema;
use Model\Cluster\Schema\Table as Table;
use Model\Cluster\Schema\Table\Column as Column;

use Model\Cluster\Schema\Table\Index\Exception\ErrorException as ErrorException;

/**
 * Abstract table index
 *
 * @category   Schema
 * @package    Table
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
abstract class AbstractIndex extends \ArrayIterator
{
    const TYPE_PRIMARY  = 'Model\Cluster\Schema\Table\Index\Primary';
    const TYPE_UNIQUE   = 'Model\Cluster\Schema\Table\Index\Unique';
    const TYPE_KEY      = 'Model\Cluster\Schema\Table\Index\Key';

    /**
     *
     * @var string
     */
    protected $_type;

    /**
     *
     * @var string
     */
    protected $_name;

    /**
     *
     * @param array $name
     * @param array $columns
     * @throws Exception\ErrorException
     */
    public function __construct($name, array $columns)
    {
        $this->_name    = (string)$name;
        $this->_type    = get_class($this);

        if (empty($columns)) {
            throw new ErrorException('Index without columns? Hm.');
        }


        foreach ($columns as $column) {
            if (!$column instanceof Column) {
                throw new ErrorException('Column is not an instance of Model\Cluster\Schema\Table\Column');
            }
        }
        
        parent::__construct($columns);
    }

    /**
     * Получить имя индекса
     *
     * @return string
     */
    public function getName()
    {
        /** @var $this AbstractIndex */
        return (string)$this->_name;
    }

    /**
     * Уникальный ли индекс
     *
     * @return bool
     */
    public function isUnique()
    {
        return ($this->getType() == self::TYPE_PRIMARY || $this->getType() == self::TYPE_UNIQUE);
    }

    /**
     * Получить тип ключа
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Является ли ключ составным
     *
     * @return bool
     */
    public function isMultiple()
    {
        return $this->count() > 1;
    }

    /**
     * @param $column
     * @return bool
     */
    public function hasColumn($column)
    {
        if ($column instanceof Column) {
            $columnName = $column->getName();
        } else {
            $columnName = (string)$column;
        }

        /** @var $col Column */
        foreach ($this as $col) {
            if ($col->getName() == $columnName) {
                return true;
            }
        }

        return false;
    }

    public function toArray($deep = true)
    {
        $result = array('name' => $this->getName(), 
                        'type' => $this->getType());
        
        if ($deep) {
            $result['columns'] = array();
            foreach ($this as $column) {
                $result['columns'][] = $column->toArray($deep);
            }
        }
        
        return $result;
    }

    /**
     * @param                             $xml
     * @param \Model\Cluster\Schema\Table $table
     * @return mixed
     * @throws \Model\Exception\ErrorException
     */
    public static function fromXml($xml, Table $table)
    {
        if (is_array($xml)) {
            $data = $xml;
        } else {
            $xml = simplexml_load_string($xml);
            $data = json_decode(json_encode((array) $xml), 1);
        }
        $data = Column::prepareXmlArray($data);

        $columnArray = array_map('trim', explode(',', $data['@attributes']['columns']));

        $columns = array();
        foreach ($columnArray as $column) {
            $col = $table->getColumn($column);

            if (!$col) {
                throw new \Model\Exception\ErrorException('Column ' . $column . ' not found');
            }

            $columns[] = $col;
        }

        switch (strtolower($data['@attributes']['type'])) {
            case 'primary':
                $type = self::TYPE_PRIMARY;
                break;
            case 'unique':
                $type = self::TYPE_UNIQUE;
                break;
            case 'key':
                $type = self::TYPE_KEY;
                break;
        }

        return new $type($data['@attributes']['name'], $columns);
    }

    public function toXml($withHeader = true, $tabStep = 5)
    {
        $tab = '    ';
        $shift = str_repeat($tab, $tabStep);
        $xml = $withHeader ? \Model\Cluster::XML_HEADER . "\n": '';

        $columns = '';
        foreach ($this as $column) {
            $columns .= $column->getName() . ',';
        }

        $typeParts = explode('\\', $this->getType());
        $type = end($typeParts);
        $xml .= $shift . '<index name="' . $this->getName() . '" columns="' . trim($columns, ', ') . '" type="' . $type . '"/>' . "\n";

        return $xml;
    }
}