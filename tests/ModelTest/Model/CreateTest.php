<?php

namespace ModelTest\Model;

use Model\TagModel;

/**
 * Class CreateTest
 *
 * @package ModelTest\Model
 * @group Model
 */
class CreateTest extends \ModelTest\Model\TestCase
{
    /**
     * @group Model
     */
    public function testTableLinks()
    {
        $tagModel = TagModel::getInstance();
        $this->assertInstanceOf('Model\TagModel', $tagModel);
    }
}
