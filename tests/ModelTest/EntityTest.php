<?php

namespace ModelTest;

/**
 * Entity test
 *
 * @category   ModelTest
 * @package    ModelTest_Entity
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 * @group      ModelTest_Entity
 */
class EntityTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        require_once __DIR__ . '/_files/TestObjectEntity.php';
        require_once __DIR__ . '/_files/TestEmptyEntity.php';
    }

    public function testFromArray()
    {
        $entity = new \TestObjectEntity(array('id' => '12', 'test1' => array(13), '_rel' => '1'));

        $entity2 = new \TestObjectEntity($entity);
        $this->assertEquals(12, $entity2->getId());

        $this->assertEquals(12, $entity->getId());
        $this->assertEquals(array(13), $entity->getTest1());
        $this->assertEquals(1, $entity->getRel());

        $mongoId = new \MongoId();
        $entity = new \TestObjectEntity(array('mongo_id' => $mongoId));
        $this->assertEquals((string)$mongoId, $entity->getMongoId());
    }

    /**
     * Тестируем приведение к типам при fromArray
     *
     * @group fromArray
     */
    public function testFromArrayTypes()
    {
        $data = array(
            'mongo_id' => '504add9b61667f98b5000006',
            'a'   => '12',
            'b'   => '90.1',
            'c'   => 'string',
            'd'   => '12',
            'e'   => 'no',
            '_test_empty' => array( 'a' => 222 )
        );

        $entity = new \TestObjectEntity($data);

        $this->assertInstanceOf('MongoId', $entity->getMongoId());

        $this->assertInternalType('bool', $entity->getA());
        $this->assertInternalType('float', $entity->getB());
        $this->assertInternalType('integer', $entity->getC());
        $this->assertInternalType('null', $entity->getD());
        $this->assertInternalType('string', $entity->getE());
        $this->assertInstanceOf('TestEmptyEntity', $entity->getTestEmpty());

        $entity = new \TestObjectEntity(array());

        $this->assertInternalType('bool', $entity->getA());
        $this->assertInternalType('float', $entity->getB());
        $this->assertInternalType('integer', $entity->getC());
        $this->assertInternalType('null', $entity->getD());
        $this->assertInternalType('string', $entity->getE());
        $this->assertInstanceOf('TestEmptyEntity', $entity->getTestEmpty());

        $this->assertInstanceOf('MongoId', $entity->getMongoId());
    }

    /**
     * Протестировать существование
     */
    public function testExists()
    {
        $data = null;
        $entity = new \TestObjectEntity($data);
        $this->assertFalse($entity->exists());

        $data = array();
        $entity = new \TestObjectEntity($data);
        $this->assertFalse($entity->exists());

/*        $data = array('not_existed_key' => 1);
        $entity = new \TestObjectEntity($data);
        $this->assertFalse($entity->exists());
*/
        $data = array('id' => 1);
        $entity = new \TestObjectEntity($data);
        $this->assertTrue($entity->exists());

        $data = array('_rel' => 1);
        $entity = new \TestObjectEntity($data);
        $this->assertTrue($entity->exists());
    }

    /**
     * Если dataTypes не определены, то мы берем любые данные в обработку
     *
     */
    public function testEmptyEntity()
    {
        $data = null;
        $entity = new \TestEmptyEntity($data);
        $this->assertFalse($entity->exists());

        $data = array();
        $entity = new \TestEmptyEntity($data);
        $this->assertFalse($entity->exists());

/*        $data = array('not_existed_key' => 1);
        $entity = new \TestEmptyEntity($data);
        $this->assertFalse($entity->exists());
*/
        $data = array('id' => 1);
        $entity = new \TestEmptyEntity($data);
        $this->assertTrue($entity->exists());

        $data = array('_rel' => 1);
        $entity = new \TestEmptyEntity($data);
        $this->assertTrue($entity->exists());
    }

    public function testToArray()
    {
        $data = array('a' => 1, 'b' => 2, '_rel' => 3);
        $entity = new \TestEmptyEntity($data);
        $this->assertEquals(array('a' => 1, 'b' => 2, '_rel' => 3), $entity->toArray());

    }

    public function testToArrayWithoutRelated()
    {
        $data = array('a' => 1, 'b' => 2, '_rel' => 3);
        $entity = new \TestEmptyEntity($data);
        $this->assertEquals(array('a' => 1, 'b' => 2), $entity->toArrayWithoutRelated());
    }
}
