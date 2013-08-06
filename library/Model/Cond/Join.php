<?php
/**
 * Condition
 *
 * LICENSE: THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NON INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @category   Cond
 * @package    Model
 * @author     Eugene Myazin <eugene.myazin@gmail.com>
 * @copyright  2008-20013 Eugene Myazin <eugene.myazin@gmail.com>
 * @license    https://github.com/meniam/model/blob/master/MIT-LICENSE.txt  MIT License
 */

namespace Model\Cond;

use Model\Cond\Exception\ErrorException as ErrorException;

/**
 *
 *
 * @category   Model
 * @package    Cond
 * @author     Eugene Myazin <meniam@gmail.com>
 * @since      14.12.12 14:24
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Join
{
    protected $_entity;
    protected $_joinType;
    protected $_table;
    protected $_condition = '';
    protected $_columns = '';
    
    public function __construct($entity, $joinType = AbstractCond::JOIN_INNER)
    {
        if (empty($entity)) {
            throw new ErrorException("Entity can not be null in join rule");
        }

        if (empty($joinType)) {
            throw new ErrorException("Join type can not be null in join rule");
        }
        
        $this->_entity   = strval($entity);
        $this->_joinType = strval($joinType);
    }
    
    public function setRule($table, $condition = '', $columns = '')
    {
        if (empty($table)) {
            throw new ErrorException("Table can not be null in join rule");
        }
        
        $this->_table     = $table;
        $this->_condition = $condition;
        $this->_columns   = $columns;
    }

    public function issetRule()
    {
        return $this->getTable() != '';
    }
    
    public function getEntity()
    {
        return $this->_entity;
    }
    
    public function getJoinType()
    {
        return $this->_joinType;
    }

    public function getTable() 
    {
        return $this->_table;
    }
    
    public function getCondition() 
    {
        return $this->_condition;
    }
    
    public function getColumns() 
    {
        return $this->_columns;
    }
}