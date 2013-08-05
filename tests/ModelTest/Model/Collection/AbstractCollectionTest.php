<?php

namespace ModelTest\Model;

require_once FIXTURES_PATH . '/TestAbstractCollection.php';

/**
 * Class CreateTest
 *
 * @package ModelTest\Model
 * @group Model
 */
class AbstractCollectionTest extends \ModelTest\Model\TestCase
{
    /**
     * @group Model
     */
    public function testCreateEmptyCollection()
    {
        $collection = new \Model\Collection\TestAbstractCollection();
        $this->assertInstanceOf('Model\Collection\TestAbstractCollection', $collection);
        $this->assertFalse($collection->exists());
    }
}
