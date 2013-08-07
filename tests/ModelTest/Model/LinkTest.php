<?php

namespace ModelTest\Model;

use Model\Cond\TopicCond;
use Model\TagModel;
use Model\TopicModel;

/**
 * Model link method's tests
 *
 * @package ModelTest
 * @group Model
 */
class LinkTest extends \ModelTest\Model\TestCase
{
    /**
     * @group Model
     */
    public function testManyToManyLink()
    {
        $topicId = 98;
        $topicModel = TopicModel::getInstance();
        $topicModel->truncate();
        $this->assertInstanceOf('Model\TopicModel', $topicModel);

        $tagId = 89;
        $secondTagId = 890;
        $tagModel = TagModel::getInstance();
        $tagModel->truncate();
        $this->assertInstanceOf('Model\TagModel', $tagModel);

        $topicData = array(
            'id' => $topicId,
            'title' => 'link_test'
        );

        $topicModel->import($topicData);
        $topic = $topicModel->getById($topicId);
        $this->assertEquals($topicId, $topic->getId());

        $tagData = array(
            'id' => $tagId,
            'name' => 'link_test'
        );

        $tagModel->import($tagData);
        $tag = $tagModel->getById($tagId);
        $this->assertEquals($tagId, $tag->getId());

        $tagData = array(
            'id' => $secondTagId,
            'name' => 'link_test2'
        );

        $tagModel->import($tagData);
        $tag = $tagModel->getById($secondTagId);
        $this->assertEquals($secondTagId, $tag->getId());

        $topicModel->linkTopicToTag($topicId, $tagId);
        $topic = $topicModel->getById($topicId, TopicCond::init()->with(TopicCond::WITH_TAG_COLLECTION));
        $this->assertEquals(1, $topic->getTagCollection()->count());

        $topicModel->linkTopicToTag($topicId, $secondTagId);
        $topic = $topicModel->getById($topicId, TopicCond::init()->with(TopicCond::WITH_TAG_COLLECTION));
        $this->assertEquals(2, $topic->getTagCollection()->count());

        $topicModel->linkTopicToTag($topicId, $secondTagId, false);
        $topic = $topicModel->getById($topicId, TopicCond::init()->with(TopicCond::WITH_TAG_COLLECTION));
        $this->assertEquals(1, $topic->getTagCollection()->count());

    }
}
