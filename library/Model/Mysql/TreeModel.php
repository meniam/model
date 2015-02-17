<?php

namespace Model\Mysql;

use Model\Collection\AbstractCollection;
use Model\Cond\AbstractCond;
use Model\Cond\TreeCond;
use Model\Db\Expr;
use Model\Entity\AbstractEntity;
use Model\Result\Result;

/**
 *
 *
 * @category   Model
 * @package    Mysql
 * @author     Eugene Myazin <meniam@gmail.com>
 * @since      14.12.12 13:21
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

        if ($cond->checkWith(TreeCond::WITH_ALL_CHILD_COLLECTION) &&
            $cond->getWith(TreeCond::WITH_ALL_CHILD_COLLECTION, $this->getRawName())
        ) {
            /** @var TreeCond $innerCond */
            $innerCond = $cond->getWith(TreeCond::WITH_ALL_CHILD_COLLECTION, $this->getRawName());

            $allChildCollection = $this->getAllChildCollectionById($data['id'], $innerCond->condIncludeSelf(false));

            if ($allChildCollection->count() > 0) {
                $data['_all_child_collection'] = $allChildCollection;
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
     * @param TreeCond $cond
     *
     * @return AbstractCollection|AbstractEntity[]
     */
    public function getParentCollectionById($id, TreeCond $cond = null)
    {
        $cond = $this->prepareCond($cond, $this->getRawName());
        $item = $this->getById($id, $cond);

        if (!$item->exists()) {
            return $this->prepareCollection(array(), $cond);
        }

        $treePath = $item->getTreePath();
        $parentListIds = explode(',', $treePath);

        if (!$cond->getCond(TreeCond::COND_INCLUDE_SELF, true)) {
            array_pop($parentListIds);
        }

        $sort = $cond->getCond(TreeCond::COND_PARENT_LIST_REVERSE, false)  ? 'DESC' : 'ASC';

        $cond->where(array($this->qi('id') => $parentListIds))
            ->order($this->qi('level') . ' ' . $sort)
            ->showQuery(true);

        return $this->getCollection($cond);
    }

    /**
     * @param $id
     * @param TreeCond $cond
     * @return mixed
     * @throws \Model\Exception\ErrorException
     */
    public function getAllChildCollectionById($id, TreeCond $cond = null)
    {
        $cond = $this->prepareCond($cond, $this->getRawName())
                     ->withoutPrepare(true);

        $itemId = $this->getFirstIdFromMixed($id);
        $item = $this->getById($itemId, $cond);

        if (!$item || !isset($item['id'])) {
            return $this->prepareCollection(array(), $cond);
        }

        $result = array();

        if ($cond->getCond(TreeCond::COND_INCLUDE_SELF, false)) {
            $result[] =  $item;
        }

        $allChildCond = $this->prepareCond($cond, $this->getRawName())
                            ->where(new Expr($this->qi('tree_path') . " LIKE '{$item['tree_path']},%'"))
                            ->order($this->qi('level') . ' ASC')
                            ->order($this->qi('pos') . ' ASC');

        $childCollection = $this->getCollection($allChildCond);

        if (!empty($result) && !empty($childCollection)) {
            $result = array_merge($result, $childCollection);
        } elseif (empty($result) && !empty($childCollection)) {
            $result = $childCollection;
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
     *
     * @param array $data
     * @param AbstractCond $cond
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

    /**
     * Get Direct child Collection
     *
     * @param $id
     * @param AbstractCond $cond
     * @return array|mixed|AbstractCollection|\Model\Entity\AbstractEntity[]|null|string
     */
    public function getChildCollection($id, AbstractCond $cond = null)
    {
        $cond = $this->prepareCond($cond, $this->getRawName());
        $id = $this->getFirstIdFromMixed($id);
        $cond->where(array($this->qi('parent_id') => $id));
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

        $cond = $this->prepareCond($cond, $this->getRawName())->where(array('parent_id' => $parentIds));
        return $this->getCollection($cond);
    }

    public function repairBranchByParent($parent = null)
    {
        $parentCollectionCond = $this->getCond()
                                    ->order($this->qi('level') . ' ASC')
                                    ->order($this->qi('pos') . ' ASC');

        $rubricCollection = $this->getCollectionByParent($parent, $parentCollectionCond);

        // If nothing to repair return result
        if ($rubricCollection->isEmpty()) {
            return true;
        }

        $pos = 1;
        $parentItem = $this->getById($parent);

        foreach ($rubricCollection as $rubric) {
            $updateData = array(
                'id'    => $rubric->getId(),
                'level' => $parentItem->getLevel() + 1,
                'tree_path' =>  trim($parentItem->getTreePath() . ',' . $rubric->getId(), ','),
                'pos' => $pos++
            );

            if (method_exists($this, 'afterPrepareRepairTreeData')) {
                $updateData = call_user_func_array(array($this, 'afterPrepareRepairTreeData'), array($updateData, $rubric, $parentItem));
            }

            $this->updateById($updateData, $rubric->getId());
            $this->repairBranchByParent($rubric);
        }

        return true;
    }
}