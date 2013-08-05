<?php

namespace ModelTest\Schema;

use Model\Cluster\Schema\Table\Index\Primary as PrimaryIndex;

use Model\Cluster\Schema as Schema;
use Model\Cluster\Schema\Table as Table;
use Model\Cluster\Schema\Table\Column as Column;

class TableTest extends \ModelTest\Schema\Table\TestCase
{
    /**
     * @var \Model\Cluster\Schema\Table
     */
    protected $_table;

    protected function setUp()
    {
        $this->_table = $this->getSchema()->getTable('product');
    }

    protected function tearDown()
    {
        $this->_table = null;
    }

    public function testGetName()
    {
        $this->assertEquals('product', $this->_table->getName());
    }

    public function testIsLinkTable()
    {
        $this->assertFalse($this->_table->isLinkTable());
    }

    /**
     * @covers Model\Cluster\Schema\Table::addIndex
     */
    public function testAddIndex()
    {
        $primaryIndex = new PrimaryIndex('PRIMARY', array(new Column(array(), $this->_table)));
        $this->_table->addIndex($primaryIndex);
        $primaryIndex = $this->_table->getIndex('PRIMARY');
        $this->assertInstanceOf('Model\Cluster\Schema\Table\Index\AbstractIndex', $primaryIndex);
    }
}

