<?php

namespace ModelTest;

class ClusterTest extends \ModelTest\TestCase
{
    /**
     * @var \Model\Cluster
     */
    private $cluster;

    protected function setUp() 
    {
        $this->cluster = new \Model\Cluster();

        $schema = $this->getSchema();
        $this->cluster->addSchema($schema);
    }

    protected function tearDown()
    {
        $this->cluster = null;
    }

    /**
     * Тестируем связи таблиц
     *   - Один к одному (по полю)
     *   - Один к одному (через таблицу связки)
     *   - Один к многим (по полю)
     *   - Один к многим (через таблицу связки)
     *   - Много ко многим (по полю)
     * @group schema
     */
    public function testTableLinks()
    {
        $schema = $this->cluster;
        $this->assertTrue($this->cluster->hasTable('linktest_product'));
        $this->assertTrue($this->cluster->hasTable('linktest_product_info'));

        $productTable = $schema->getTable('linktest_product');
        $this->assertInstanceOf('\Model\Cluster\Schema\Table', $productTable);
    }

    /**
     * @group schema
     */
    public function testCreateSchema()
    {
        $this->assertTrue($this->cluster->hasTable('product'));
        $this->assertFalse($this->cluster->hasTable(null));

        $productTable = $this->cluster->getTable('product');
        $this->assertInstanceOf('\Model\Cluster\Schema\Table', $productTable);

        $this->assertNull($this->cluster->getTable('not_exists'));

//        $allInclusiveTable = $this->_schema->getTable('all_inclusive');
  //      $this->assertInstanceOf('Model_Schema_Table', $allInclusiveTable);
        
    //    $this->assertInstanceOf('Model_Schema_Table_Index_Unique', $allInclusiveTable->getIndex('idx_uniq_admin_creator'));
    }
}
