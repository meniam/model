<?php

namespace ModelTest\Cluster\Schema\Table;
use ModelTest\Cluster\Schema\Table\TestCase as TestCase;

class ColumnTest extends TestCase
{
    /**
     * @var Model_Schema_Table
     */
    protected $_table;

    protected function setUp()
    {
        $this->_table  = $this->getSchema()->getTable('product');
    }

    protected function tearDown()
    {
        $this->_table  = null;
    }

    public function testSetUniqueFlag()
    {
        $column = $this->_table->getColumn('id');
        $this->assertInstanceOf('\Model\Cluster\Schema\Table\Column', $column);


        $this->assertTrue($column->isUnique());
        $column->setUniqueFlag(false);
        $this->assertFalse($column->isUnique());
        $column->setUniqueFlag(true);
    }

    public function testIsNullable()
    {
        $column = $this->_table->getColumn('id');
        $this->assertInstanceOf('\Model\Cluster\Schema\Table\Column', $column);

        $this->assertFalse($column->isNullable());

        $column = $this->_table->getColumn('nullable');
        $this->assertInstanceOf('\Model\Cluster\Schema\Table\Column', $column);
        $this->assertTrue($column->isNullable());
    }

    /**
     */
    public function testGetTable()
    {
        $column = $this->_table->getColumn('id');
        $this->assertInstanceOf('\Model\Cluster\Schema\Table\Column', $column);
        $this->assertInstanceOf('\Model\Cluster\Schema\Table', $column->getTable());
        $this->assertEquals('product', $column->getTable()->getName());
        $this->assertEquals('model_test', $column->getSchemaName());
        $this->assertEquals('id', $column->getName());
    }


    /**
     */
    public function testToArray()
    {
        $column = $this->_table->getColumn('id');
        $columnArray = $column->toArray();

        $this->assertInternalType('array', $columnArray);

        $arrayFields = array('table_catalog', 'table_schema', 'table_name', 'column_name', 'ordinal_position', 'column_default', 'is_nullable', 'data_type', 'character_maximum_length', 'character_octet_length', 'numeric_precision', 'numeric_scale', 'character_set_name', 'collation_name', 'column_type', 'column_comment', 'is_unique');

        foreach ($arrayFields as $field) {
            $this->assertArrayHasKey($field, $columnArray);
        }
    }
}
?>
