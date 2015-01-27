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

use Model\AbstractModel;

class TreeCond extends Cond
{
    const COND_INCLUDE_SELF = 'include_self';

    const COND_PARENT_LIST_REVERSE = 'parent_list_reverse';

    const WITH_ALL_CHILD_COLLECTION = 'all_child_collection';

    /**
     * @param      $type
     * @param bool $includeSelf
     *
     * @return $this
     */
    public function withChild($type, $includeSelf = false)
    {
        $this->condIncludeSelf($includeSelf);
        $this->with('with_child', $type);
        return $this;
    }

    public function withChildCollection($type, $includeSelf = false)
    {
        $this->condIncludeSelf($includeSelf);
        $this->with('with_child_collection', $type);
        return $this;
    }

    /**
     * @param bool $is
     * @return $this
     * @throws \Model\Exception\ErrorException
     */
    public function condIncludeSelf($is = true)
    {
        $this->cond(self::COND_INCLUDE_SELF, (bool)$is);
        return $this;
    }

    /**
     * @param bool $is
     * @return $this
     * @throws \Model\Exception\ErrorException
     */
    public function condParentListReverse($is = true)
    {
        $this->cond(self::COND_PARENT_LIST_REVERSE, (bool)$is);
        return $this;
    }

    public function withAllChildCollection(TreeCond $cond = null)
    {
        if (!$cond) {
            $cond = $this->init(self::WITH_ALL_CHILD_COLLECTION, $this->getName());
        }

        $this->with($cond);
        return $this;
    }
}