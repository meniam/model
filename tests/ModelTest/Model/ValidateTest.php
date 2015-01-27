<?php

namespace ModelTest\Model;

use Model\TagModel;

/**
 * @package ModelTest\Model
 * @group Model
 */
class ValidateTest extends TestCase
{
    /**
     * @group Model
     * @group run
     */
    public function testValidateValue()
    {
        /** @var TagModel $tagModel */
        $tagModel = TagModel::getInstance();

        $id = "12 баксов";
        $this->assertTrue($tagModel->validateValue($id, 'id'));
    }
}
