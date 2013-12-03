<?php


namespace ModelTest\Model;

use Model\TagModel;

/**
 * Class CreateTest
 *
 * @package ModelTest\Model
 * @group Model
 */
class DefaultTest extends TestCase
{
    /**
     * @group Model
     */
    public function testApplyDefaultValues()
    {
        $input = array('id' => '1', '_inner' => array());
        $default = array('id' => 100, 'name' => 'test');
        $result = array();
        TagModel::getInstance()->applyDefaultValues($input, $default, $result);

        $this->assertTrue(isset($result['name']));
        $this->assertEquals('test', $result['name']);


        $input = array('id' => '1', '_inner' => array());
        $default = array('id' => 100, 'name' => 'test');
        $result = array('name' => 'notest');
        TagModel::getInstance()->applyDefaultValues($input, $default, $result);
    }
}
