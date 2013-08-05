<?php

namespace ModelTest;

require_once __DIR__ . '/_files/ProductCond.php';

use Model\Cond\Cond;
use Model\Cond\ProductCond;
use Model\Mysql\AbstractModel;

/**
 * Entity test
 *
 * @category   ModelTest
 * @package    ModelTest_Entity
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 * @group      ModelTest_Cond
 */
class CondTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Model\Cond\ProductCond
     */
    protected $_cond;

    public function setUp()
    {
        $this->_cond = ProductCond::init();
    }

    public function testCreation()
    {
        $cond = new Cond('product');
        $this->assertInstanceOf('Model\Cond\Cond', $cond);

        $cond = AbstractModel::condFactory('product');
        $this->assertInstanceOf('Model\Cond\ProductCond', $cond);
        $this->assertEquals('product', $cond->getName());
        $this->assertEquals('Product', $cond->getEntityName());

        $cond = AbstractModel::condFactory('product_collection');
        $this->assertInstanceOf('Model\Cond\ProductCond', $cond);
        $this->assertEquals('product_collection', $cond->getName());
        $this->assertEquals('Product', $cond->getEntityName());

        $cond = AbstractModel::condFactory('product_count');
        $this->assertInstanceOf('Model\Cond\ProductCond', $cond);
        $this->assertEquals('product_count', $cond->getName());
        $this->assertEquals('Product', $cond->getEntityName());

        $cond = AbstractModel::condFactory('just_not_existed');
        $this->assertInstanceOf('Model\Cond\Cond', $cond);
        $this->assertEquals('just_not_existed', $cond->getName());
        $this->assertEquals('JustNotExisted', $cond->getEntityName());

        $cond = AbstractModel::condFactory('just_not_existed', 'product');
        $this->assertInstanceOf('Model\Cond\ProductCond', $cond);
        $this->assertEquals('just_not_existed', $cond->getName());
        $this->assertEquals('JustNotExisted', $cond->getEntityName());
        $this->assertEquals('\Model\Entity\ProductEntity', $cond->getEntityClassName());

    }

    public function testGetEntity()
    {
        $this->assertEquals('product', $this->_cond->getEntityVar());
    }

    public function testFrom()
    {
        $this->_cond->from('product');
        $this->assertEquals('product', $this->_cond->getCond(Cond::FROM));

        $this->_cond->from('product2');
        $this->assertEquals('product', $this->_cond->getCond(Cond::FROM));
    }

    public function testWhere()
    {
        $this->_cond->where(array('id' => 2));
    }
}
