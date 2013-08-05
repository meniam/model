<?php

namespace ModelTest\Model;

use Model\TagModel;

/**
 * @package ModelTest\Model
 * @group Model
 */
class UpdateTest extends TestCase
{
    /**
     * @group Model
     */
    public function testUpdate()
    {
        /** @var TagModel $tagModel */
        $tagModel = TagModel::getInstance();
        $tagModel->truncate();

        $id = 98223;
        $name = 'update test';
        $tagData = array(
            'id' => $id,
            'name' => $name,
            'unknown_field' => 'a-a-a-a'
        );

        $tagModel->import($tagData);
        $entity = $tagModel->getById($id);
        $this->assertEquals($id, $entity->getId());
        $this->assertEquals($name, $entity->getName());

        $name = 'update2 test';
        $updateTagData = array(
            'id' => $id,
            'name' => $name,
            'unknown_field' => 'a-a-a-a'
        );

        $tagModel->update($updateTagData, array('id' => $id));

        $entity = $tagModel->getById($id);
        $this->assertEquals($id, $entity->getId());
        $this->assertEquals($name, $entity->getName());
    }
}
