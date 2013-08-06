<?php

namespace ModelTest\Model;

/**
 * getIdsFromMixed functionality test
 *
 * @package ModelTest
 * @group Model
 */
class GetIdsFromMixedTest extends \ModelTest\Model\TestCase
{
    /**
     * @group Model
     */
    public function testGetIdsFromMixed()
    {
        $productModel = \Model\ProductModel::getInstance();

        $this->assertEquals(array(0 => 1), $productModel->getIdsFromMixed(1));
        $this->assertEquals(array(), $productModel->getIdsFromMixed(null));
    }
}
