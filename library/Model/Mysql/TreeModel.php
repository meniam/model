<?php

namespace Model\Mysql;

use Model\Collection\AbstractCollection;
use Model\Cond\AbstractCond;
use Model\Entity\AbstractEntity;
use Model\Result\Result;

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
     * @return AbstractCollection|AbstractEntity[]
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

    /**
     * @param              $data
     * @param AbstractCond $cond
     *
     * @return null
     */
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

    /**
     * Хук вызываемый после обработки данных при добавлении или изменении
     *
     * Подобным образом работают следующие методы:
     *  - afterPrepareOnAdd - выполняется после обработки данных только при добавлении
     *  - afterPrepareOnUpdate - выполняется после обработки данных только при обновлении
     */
    protected function afterPrepareOnAddOrUpdate(array $data = null, AbstractCond $cond = null)
    {
        if (isset($data['parent_id'])) {
            /** @var  $parent */
            $parent = $this->getById($data['parent_id']);

            $data['level'] = $parent->getLevel() + 1;
        }
    }

    protected function afterAdd(Result $result, $data)
    {
        if (!$result->isError() && $result->getResult()) {
            $parentId = isset($data['parent_id']) ? $data['parent_id'] : null;
            $parent = $this->getById($parentId);

            $updateData = array(
                'tree_path' => trim($parent->getTreePath() . ',' . $result->getResult(), ','));

            $this->updateById($updateData, $result->getResult());
        }
    }

    public function getChildCollection($id, AbstractCond $cond = null)
    {
        $cond = $this->prepareCond($cond, $this->getRawName());
        $id = $this->getFirstIdFromMixed($id);

        $cond->where(array('parent_id' => $id));

        return $this->getCollection($cond);
    }

    /**
     * @param $parent
     * @param AbstractCond $cond
     * @return array|mixed|AbstractCollection|\Model\Entity\AbstractEntity[]|null|string
     * @throws \Model\Exception\ErrorException
     */
    public function getCollectionByParent($parent, AbstractCond $cond = null)
    {
        $parentIds = $this->getIdsFromMixed($parent);

        if (!$parentIds) {
            $parentIds = null;
        }

        $cond = $this->prepareCond($cond, $this->getRawName())
                ->where(array('parent_id' => $parentIds));

        return $this->getCollection($parentIds, $cond);
    }

    public function repairBranchByParent($parent = null)
    {
        $rubricCollection = $this->getCollectionByParent($parent);

        if ($rubricCollection->isEmpty()) {
            return true;
        }

        $parentItem = $this->getById($parent);

        print_r($parentItem);
        die;
    }
}