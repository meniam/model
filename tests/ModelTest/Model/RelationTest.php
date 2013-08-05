<?php

namespace ModelTest\Model;

use Model\Cond\ProductCond;
use Model\Cond\TagAliasCond;
use Model\Cond\TagCond;
use Model\Cond\TagInfoCond;
use Model\Cond\TagStatCond;
use Model\Cond\TopicCond;
use Model\Cond\UserCond;
use Model\Entity\LinktestOtoSecondEntity;
use Model\Entity\TagEntity;
use Model\Entity\UserEntity;
use Model\LinktestOtoFirstModel;
use Model\LinktestOtoSecondModel;
use Model\LinktestOtoThirdModel;
use Model\LinktestProductModel;
use Model\TagModel;
use Model\TagAliasModel;
use Model\TagStatModel;
use Model\TopicModel;
use Model\UserModel;

/**
 * Class RelationTest
 *
 * @package ModelTest\Model
 * @group   Model
 */
class RelationTest extends \ModelTest\Model\TestCase
{
    /**
     * Тестируем выборку с помощью WITH когда связть One To Many
     * @covers \Model\AbstractModel::__call
     */
    public function testWithFetchOnOneToManyLink()
    {
        $topicModel = TopicModel::getInstance();
        $tagModel = TagModel::getInstance();

        $topicModel->delete(array('id' => 8762));

        $topicData = array(
            'id' => 8762,
            'title' => 'one_to_many test',
            '_base_tag' => array(
                'id' => 123,
                'name' => 'testWithFetchOnOneToManyLink test inner'
            )
        );

        $result = $topicModel->import($topicData);

        $cond = TopicCond::init()
            ->with(TopicCond::WITH_BASE_TAG);

        $entity = $topicModel->getById(8762, $cond);
        $this->assertEquals(8762, $entity->getId());
        $this->assertEquals(123, $entity->getBaseTag()->getId());


        $tagData = array(
            'id' => 9811,
            'name' => 'one_to_one test',
            '_tag_info' => array(
                'id' => 232,
                'info' => 'test'
            )
        );


        $tagModel->import($tagData);


        $cond = TagCond::init()
            ->with(TagCond::WITH_TAG_INFO);

        $entity = $tagModel->getById(9811, $cond );
        $this->assertEquals(9811, $entity->getId());
        $this->assertEquals(232, $entity->getTagInfo()->getId());
    }


    /**
     * @group !disable
     */
    public function testJoinOneToOne()
    {
        $tagModel = TagModel::getInstance();
        $tagStatModel = TagStatModel::getInstance();
        $tagAliasModel = TagAliasModel::getInstance();

        $tagModel->truncate();
        $tagStatModel->truncate();
        $tagAliasModel->truncate();

        $tagData = array(
            'id' => 10,
            'name' => 'test',
            '_tag_alias' => array(
                'id' => 11,
                'name' => 'test2'
            ),
            '_tag_stat' => array(
                'topic_count' => 12
            )
        );

        $tagModel->import($tagData);

        $tagCond = $tagModel->getCond()
            ->columns(array('tag.*', '_tag_alias_name' => 'tag_alias.name'))
            ->join(TagCond::JOIN_TAG_ALIAS);

        /** @var TagEntity $tag */
        $tag = $tagModel->getById(10, $tagCond);
        $this->assertEquals('test2', $tag->get('_tag_alias_name'));
    }



    /**
     * Тестируем выборку с помощью WITH когда связть Many To Many
     * @covers \Model\AbstractModel::__call
     */
    public function testWithFetchManyToMany()
    {
        $tagModel = TagModel::getInstance();
        $tagModel->truncate();

        $topicModel = TopicModel::getInstance();
        $topicModel->truncate();

        $topicData = array(
            'id'              => '12',
            'title'           => 'topic_1',
            '_tag_collection' => array(
                array(
                    'name' => 'topic_tag_1'
                ),
                array(
                    'name' => 'topic_tag_2'

                )
            )
        );

        $topicModel->import($topicData);

        $topicCond = TopicCond::init()
            ->with(TopicCond::WITH_TAG)
            ->with(TopicCond::WITH_TAG_COLLECTION);

        $topic = $topicModel->getById(12, $topicCond);

        $this->assertEquals('topic_1', $topic->getTitle());
        $this->assertInstanceOf('Model\Entity\TagEntity', $topic->getTag());
        $this->assertEquals('topic_tag_1', $topic->getTag()->getName());

        $this->assertInstanceOf('Model\Collection\TagCollection', $topic->getTagCollection());
        $this->assertEquals(2, $topic->getTagCollection()->count());
    }

    public function testTagImport()
    {
        $tagModel = TagModel::getInstance();
        $tagStatModel = TagStatModel::getInstance();
        $tagAliasModel = TagAliasModel::getInstance();

        $tagModel->truncate();
        $tagStatModel->truncate();
        $tagAliasModel->truncate();

        $tagStatData  = array(
            'id' => 20,
            'topic_count' => 30,
            '_tag' => array('id' => 20,
                            'name' => 'test_20')
        );

        $result = $tagStatModel->import($tagStatData);

        $tagStatCond = $tagStatModel->getCond()
            ->with(TagStatCond::WITH_TAG);
        $tagStat = $tagStatModel->getById(20, $tagStatCond);

        $this->assertEquals(20, $tagStat->getId());
        $this->assertEquals('test_20', $tagStat->getTag()->getName());

        $tagModel->truncate();
        $tagStatModel->truncate();
        $tagAliasModel->truncate();

        $tagData = array(
            'id' => 10,
            'name' => 'test',
            '_tag_alias' => array(
                'id' => 11,
                'name' => 'test2'
            ),
            '_tag_stat' => array(
                'topic_count' => 12
            )
        );

        $result = $tagModel->import($tagData);
        $this->assertEquals('10', $result->getResult());

        $tagCond = $tagModel->getCond()
                        ->with(TagCond::WITH_TAG_ALIAS)
                        ->with(TagCond::WITH_TAG_STAT);
        $tag = $tagModel->getById(10, $tagCond);

        $this->assertEquals('test2', $tag->getTagAlias()->getName());
        $this->assertEquals(10, $tag->getId());
        $this->assertEquals(10, $tag->getTagStat()->getId());

        $tag = $tagModel->getByTagStat(10);
        $this->assertEquals('test', $tag->getName());

        $tag = $tagModel->getByTagAlias(11);
        $this->assertEquals('test', $tag->getName());

        $tag = $tagModel->getByTagStat(10);
        $this->assertEquals('test', $tag->getName());

        $tagStat = $tagStatModel->getByTag(10);
        $this->assertEquals(12, $tagStat->getTopicCount());

        $tagStat = $tagAliasModel->getByTag(10);
        $this->assertEquals('test2', $tagStat->getName());
    }




    /**
     * @group shl
     */
    public function testOneToOne()
    {
        $modelFirst = LinktestOtoFirstModel::getInstance();
        $this->assertInstanceOf('Model\LinktestOtoFirstModel', $modelFirst);

        $modelSecond = LinktestOtoSecondModel::getInstance();
        $this->assertInstanceOf('Model\LinktestOtoSecondModel', $modelSecond);

        $modelThird = LinktestOtoThirdModel::getInstance();
        $this->assertInstanceOf('Model\LinktestOtoThirdModel', $modelThird);

        $cond = $modelSecond->getCond()
            ->where(array('id' => 1));
        $modelSecond->delete($cond);

        $cond = $modelFirst->getCond()
            ->where(array('id' => 2));
        $modelFirst->delete($cond);

        $cond = $modelThird->getCond()
            ->where(array('id' => 4));
        $modelThird->delete($cond);


        $data = array('id'                        => 1,
                      '_linktest_oto_first_alias' => array(
                          'id' => 3
                      ),
                      '_linktest_oto_first'       => array(
                          'id' => 2
                      ),
                      '_third'       => array(
                          'id' => 4
                      ),
        );

        $modelSecond->import($data);
        $item = $modelSecond->getByLinktestOtoFirst(2);
        $this->assertEquals(1, $item->getId());
        $this->assertEquals(2, $item->getLinktestOtoFirstId());
        $this->assertEquals(3, $item->getLinktestOtoFirstAliasId());
        $this->assertEquals(4, $item->getThirdId());

        $item = $modelFirst->getByLinktestOtoSecond(1);
        $this->assertEquals(2, $item->getId());

        $item = $modelFirst->getByLinktestOtoSecond(1);
        $this->assertEquals(2, $item->getId());


        $tagAliasModel = TagAliasModel::getInstance();

        $tagAliasData = array(
            'id' => 876,
            'name' => 'one_to_many test',
            '_tag' => array(
                'id' => 982,
                'name' => 'one_to_many test inner'
            )
        );

        $cond = TagAliasCond::init()
            ->with(TagAliasCond::WITH_TAG);

        $tagAliasModel->import($tagAliasData);
        $entity = $tagAliasModel->getById(876, $cond);
        $this->assertEquals(876, $entity->getId());
    }

    /**
     * @group shl
     */
    public function testOneToMany()
    {
        $modelFirst = LinktestProductModel::getInstance();
        $cond = $modelFirst->getCond()
            ->where(array('id' => 1));
        $modelFirst->delete($cond);

        $data = array('id'                        => 4,
                      '_linktest_product_info' => array(
                          'id' => 11
                      ));

        $modelFirst->import($data);
        $item = $modelFirst->getByLinktestProductInfo(11);
        $this->assertEquals(4, $item->getId());
    }

}

