<?php

namespace Model\Paginator\Adapter;

use \Model\Paginator\Exception\ErrorException;

/**
 * Description
 *
 * @category   Model
 * @package    Paginator
 * @author     Eugene Myazin <meniam@gmail.com>
 * @since      26.12.12 17:17
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class ArraySet implements AdapterInterface
{
    private $data;

    public function __construct(array $select = null)
    {
        $this->data = $select;
    }

    public function getItems()
    {
        return $this->data;
    }


    /**
     * Returns the total number of rows in the result set.
     *
     * @return integer
     */
    public function count()
    {
        return count($this->data);
   }
}