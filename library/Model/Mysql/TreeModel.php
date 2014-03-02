<?php

namespace Model\Mysql;

use Model\Collection\EbayRubricCollection;
use Model\Cond\AbstractCond;

/**
 *
 *
 * @category   Model
 * @package    Mysql
 * @author     Eugene Myazin <meniam@gmail.com>
 * @since      14.12.12 13:21
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class TreeModel extends AbstractModel
{
    public function beforePrepare($data, AbstractCond $cond = null)
    {
        $cond = $this->prepareCond($cond);
        if ($cond->checkWith('with_child_collection') && $cond->getWith('with_child_collection', $this->getRawName()) instanceof AbstractCond) {
            if ($res = $this->getChildCollectionByData($data, $cond->getWith('with_child_collection', $this->getRawName()))) {
                $data['_child_collection'] = $res;
            }
        }

        if ($cond->checkWith('with_all_child_collection') && $cond->getWith('with_all_child_collection', $this->getRawName()) instanceof AbstractCond) {
            if ($res = $this->getChildCollectionByData($data, $cond->getWith('with_all_child_collection', $this->getRawName())->with('with_all_child', $this->getRawName()))) {
                $data['_all_child_collection'] = $res;
            }
        }

        if ($cond->checkWith('with_parent_collection') && $cond->getWith('with_parent_collection', $this->getRawName()) instanceof AbstractCond) {
            if ($res = $this->getParentCollectionById($data['id'], $cond->getWith('with_parent_collection', $this->getRawName()))) {
                $data['_parent_collection'] = $res;
            }
        }
        return $data;
    }

    /**
     * @param              $id
     * @param AbstractCond $cond
     *
     * @return EbayRubricCollection
     */
    public function getParentCollectionById($id, AbstractCond $cond = null)
    {
        $cond = $this->prepareCond($cond, $this->getRawName());

        $item = $this->getById($id, $cond);

        $result = array();
        if ($cond->checkCond('include_self')) {
            $result[] =  $item;
        }

        while ($item->exists()) {
            $item = $this->getById($item->getParentId(), $cond);

            if ($item->exists()) {
                $result[] = $item->toArray();
            }
        }

        return $this->prepareCollection($result, $cond);
    }


    public function getChildCollectionByData($data, AbstractCond $cond = null)
    {
        if (isset($data['id'])) {
            $result = $this->getCollectionByParent($data['id'], $cond);
            if ($result->count() == 0) {
                return null;
            } else {
                return $result;
            }
        }

        return null;
    }
}