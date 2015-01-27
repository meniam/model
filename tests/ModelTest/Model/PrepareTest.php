<?php

namespace ModelTest\Model;

use Model\Cond\TagCond;
use Model\Cond\TopicCond;
use Model\TagModel;
use Model\TopicModel;

/**
 * Class PrepareTest
 *
 * @package ModelTest\Model
 * @group   Model
 */
class PrepareTest extends \ModelTest\Model\TestCase
{
    /**
     * Test for fetch aliased related data in prepare on Many To Many relation
     */
    public function testFetchWithInPrepeareDirectlyOnManyToManyLink()
    {
        /** @var TopicModel $tagModel */
        $topicModel = TopicModel::getInstance();
        $topicModel->delete(array('id' => 12));

        $topicData = array('id'              => 12,
                           'title'           => 'test prepare',
                           '_additional_tag' => array(
                               'id'   => 123,
                               'name' => 'additional_tag test prepare'
                           ));

        $result = $topicModel->import($topicData);

        $this->assertEquals(12, $result->getResult());

        $cond = TopicCond::init()
                    ->with(TopicCond::WITH_ADDITIONAL_TAG);
        $entity = $topicModel->getById(12, $cond);

        $this->assertTrue($entity->getAdditionalTag()->exists());
        $this->assertEquals(123, $entity->getAdditionalTag()->getId());
        $this->assertEquals('additional_tag test prepare', $entity->getAdditionalTag()->getName());
    }

    /**
     * Test for fetch aliased related data in prepare on Many To Many relation
     */
    public function testAliasedFetchWithInPrepeareDirectlyOnManyToOneLink()
    {
        /** @var TagModel $tagModel */
        $tagModel = TagModel::getInstance();

        /** @var TopicModel $tagModel */
        $topicModel = TopicModel::getInstance();

        $tagModel->delete(array('id' => 800));

        $baseTagData = array(
            'id' => 800,
            'name' => 'base_Tag test one to one',
            '_topic_as_base_tag' => array(
                'id' => 801,
                'title' => 'base_topic test one to one inner'
            ),
            '_topic_as_additional_tag' => array(
                'id'   => 300,
                'title' => 'base_topic test one to one inner'
            ),
        );

        $result = $tagModel->import($baseTagData);

        $cond = TopicCond::init()
            ->with(TopicCond::WITH_BASE_TAG)
            ->with(TopicCond::WITH_ADDITIONAL_TAG);
        $entity = $topicModel->getById(801, $cond);

        $this->assertTrue($entity->getBaseTag()->exists());
        $this->assertEquals(800, $entity->getBaseTag()->getId());
        $this->assertEquals('base_Tag test one to one', $entity->getBaseTag()->getName());
        $this->assertFalse($entity->getAdditionalTag()->exists());

        $cond = TopicCond::init()
            ->with(TopicCond::WITH_BASE_TAG)
            ->with(TopicCond::WITH_ADDITIONAL_TAG);
        $entity = $topicModel->getById(300, $cond);

        $this->assertFalse($entity->getBaseTag()->exists());
        $this->assertTrue($entity->getAdditionalTag()->exists());
        $this->assertEquals(800, $entity->getAdditionalTag()->getId());
        $this->assertEquals('base_Tag test one to one', $entity->getAdditionalTag()->getName());

        $topicModel->delete(array('id' => 10));

        $topicData = array('id'        => 10,
                           'title'     => 'test prepare',
                           '_base_tag' => array(
                               'id'   => 124,
                               'name' => 'base_tag test prepare'
                           ));

        $topicModel->import($topicData);

        $cond = TopicCond::init()
            ->with(TopicCond::WITH_BASE_TAG);
        $entity = $topicModel->getById(10, $cond);

        $this->assertTrue($entity->getBaseTag()->exists());
        $this->assertEquals(124, $entity->getBaseTag()->getId());
        $this->assertEquals('base_tag test prepare', $entity->getBaseTag()->getName());



    }

    /**
     * @group Model
     */
    public function testPrepare()
    {
        /** @var TagModel $tagModel */
        $tagModel = TagModel::getInstance();
        $this->assertInstanceOf('Model\TagModel', $tagModel);
        $this->assertInstanceOf('Model\Cond\TagCond', $tagModel->getCond());

        $tagModel->delete(TagCond::init()->where(array('id' => 1)));

        $tagData = array(
            'id'   => 1,
            'name' => 'test prepare'
        );

        $tagModel->import($tagData);

        $cond = TagCond::init()
            ->with(TagCond::WITH_TAG_ALIAS);

        $tag = $tagModel->getById(1, $cond);
        $this->assertInstanceOf('Model\Entity\TagEntity', $tag);
        $this->assertEquals(1, $tag->getId());
    }


}
