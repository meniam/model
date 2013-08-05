<?php

namespace ModelTest\Cluster\Schema\Table\Link;
use ModelTest\Cluster\Schema\Table\Link\TestCase as TestCase;

/**
 * @group link
 */
class LinkTest extends TestCase
{
    /**
     * @var \Model\Cluster\Schema\Table
     */
    protected $_table;

    /**
     * @var \Model\Cluster\Schema\Table\Link\ManyToMany;
     */
    protected $_link;

    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->_table  = $this->getSchema()->getTable('product');
        $this->_link = new \Model\Cluster\Schema\Table\Link\ManyToMany(
                            $this->getSchema()->getTable('product')->getColumn('id'),
                            $this->getSchema()->getTable('tag')->getColumn('id'),
                            \Model\Cluster\Schema\Table\Link\AbstractLink::RULE_NO_ACTION,
                            \Model\Cluster\Schema\Table\Link\AbstractLink::RULE_NO_ACTION,
                            $this->getSchema()->getTable('product_tag_link')->getColumn('product_id'),
                            $this->getSchema()->getTable('product_tag_link')->getColumn('tag_id'));
    }

    /**
     * @group
     */
    public function testAlias()
    {
        $table = $this->getSchema()->getTable('linktest_oto_second');

        $link = $table->getLink('OneToOne___LinktestOtoSecond___LinktestOtoFirstAlias');
        $this->assertEquals('OneToOne___LinktestOtoSecond___LinktestOtoFirstAlias', $link->getName());

        $this->assertEquals('linktest_oto_second', $link->getLocalTable()->getName());
        $this->assertEquals('linktest_oto_first_alias_id', $link->getLocalColumn()->getName());
        $this->assertEquals('linktest_oto_second', $link->getLocalEntity());

        $this->assertEquals('linktest_oto_first', $link->getForeignTable()->getName());
        $this->assertEquals('id', $link->getForeignColumn()->getName());
        $this->assertEquals('linktest_oto_first_alias', $link->getForeignEntity());

    }

    /*
    protected function tearDown()
    {
        $this->getSchema() = null;
        $this->_table  = null;
        $this->_link  = null;
    }*/

    public function testGetName()
    {
        $this->assertEquals('ManyToMany___Product___Tag', $this->_link->getName());
    }

    public function testGetLinkType()
    {
        $this->assertEquals(\Model\Cluster\Schema\Table\Link\AbstractLink::LINK_TYPE_MANY_TO_MANY, $this->_link->getLinkType());
    }

    public function testGetLocalTable()
    {
        $this->assertInstanceOf('Model\Cluster\Schema\Table', $this->_link->getLocalTable());
        $this->assertEquals('product', $this->_link->getLocalTable()->getName());
    }

    public function testGetForeignTable()
    {
        $this->assertInstanceOf('Model\Cluster\Schema\Table', $this->_link->getForeignTable());
        $this->assertEquals('tag', $this->_link->getForeignTable()->getName());
    }

    public function testGetLocalColumn()
    {
        $this->assertInstanceOf('Model\Cluster\Schema\Table\Column', $this->_link->getLocalColumn());
        $this->assertEquals('id', $this->_link->getForeignColumn()->getName());
    }


    public function testGetForeignColumn()
    {
        $this->assertInstanceOf('Model\Cluster\Schema\Table\Column', $this->_link->getForeignColumn());
        $this->assertEquals('id', $this->_link->getForeignColumn()->getName());
    }

    public function testToArray()
    {
        $expectedArray = array(
                            'name' => 'ManyToMany___Product___Tag',
                            'type' => $this->_link->getLinkType(),
                            'local_table' => 'product',
                            'local_column' => 'id',
                            'foreign_table' => 'tag',
                            'foreign_column' => 'id',
                            'rule_update' => \Model\Cluster\Schema\Table\Link\AbstractLink::RULE_NO_ACTION,
                            'rule_delete' => \Model\Cluster\Schema\Table\Link\AbstractLink::RULE_NO_ACTION,
                            'link_table' => 'product_tag_link',
                            'link_table_local_column' => 'product_id',
                            'link_table_foreign_column' => 'tag_id',
                            'local_entity_alias' => 'product',
                            'foreign_entity_alias' => 'tag',
                            'is_direct' => true);

        $this->assertEquals($expectedArray, $this->_link->toArray());
    }
}

