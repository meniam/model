<?php

namespace ModelTest\Model;

use Model\TagModel;

/**
 * Class CreateTest
 *
 * @package ModelTest\Model
 * @group Model
 */
class GetterTest extends \ModelTest\Model\TestCase
{

    public function testGetMethodWhenOneFieldEntity()
    {
        TagModel::getInstance()->delete(array('name' => 'testGetMethodWhenOneFieldEntity'));

        TagModel::getInstance()->import(
            array('name' => 'testGetMethodWhenOneFieldEntity',
                  '_tag_alias' => array(
                      'name' => 'testGetMethodWhenOneFieldEntity_alias'
                  )
            )
        );

        $tag = TagModel::getInstance()->getByName('testGetMethodWhenOneFieldEntity');
        $this->assertTrue($tag->exists());

        $tagAlias = TagAliasModel::getInstance()->getByTagAndName($tag, 'testGetMethodWhenOneFieldEntity_alias');
        $this->assertTrue($tagAlias->exists());
    }
}
