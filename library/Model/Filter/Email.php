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
 * @category   Cond
 * @package    Model
 * @author     Eugene Myazin <eugene.myazin@gmail.com>
 * @copyright  2008-20013 Eugene Myazin <eugene.myazin@gmail.com>
 * @license    https://github.com/meniam/model/blob/master/MIT-LICENSE.txt  MIT License
 */
namespace Model\Filter;

use Zend\Filter\AbstractFilter;

/***
 * Class Email
 * @package Model\Filter
 */
class Email extends AbstractFilter
{
    public function filter($value)
    {
        $value = strip_tags($value);
        if (substr($value, 0, 7) == 'mailto:') {
            $value = substr($value, 7);
        }

        $value = preg_replace('#[\[\(]\s*(@|at)\s*[\]\)]#usi', '@', $value);

        return preg_replace('#[^0-9a-zA-Z\.\@\-\_]+#usi', '', $value);
    }
}
