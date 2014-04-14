<?php

namespace ModelTest\Model;

require_once FIXTURES_PATH . '/EntityTestEntity.php';

use Model\Entity\EntityTestEntity;
use Model\TagModel;

/**
 * Class CreateTest
 *
 * @package ModelTest\Model
 * @group Model
 */
class EntityTest extends \ModelTest\Model\TestCase
{
    /**
     * @group Model
     * @group speed
     */
    public function testCreateEntity()
    {
        $entity = new EntityTestEntity();
        $this->assertInstanceOf('Model\Entity\EntityTestEntity', $entity);
        $this->assertInternalType('int', $entity->getId());
        $this->assertEquals(0, $entity->getId());
        $this->assertEquals('', $entity->getName());

        $dataSrc = array('id' => '1', 'name' => 'Vasya');
        $entity = new EntityTestEntity($dataSrc);
        $this->assertInternalType('int', $entity->getId());
        $this->assertEquals(1, $entity->getId());
        $this->assertInternalType('string', $entity->getName());

        /** SPEEDTEST
        $start = microtime(true);
        for ($i=0; $i<1000; $i++) {
            $data = array('id' => $i+1,
                          'something_else' => '',
                          'name' => '=================================================================',
                          '_tag' => array('id' => 1));

            $entity = new EntityTestEntity($data);
            $entity->getId(); // need for test
            $entity->getName(); // need for test
            $entity->getStatus(); // need for test
            $entity->getPrice();
            $entity->getTag();
            $this->assertInstanceOf('Model\Entity\TagEntity', $entity->getTag());
        }
        SPEEDTEST */
    }
}
