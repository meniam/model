<?php

namespace ModelTest;

class SchemaTest extends \ModelTest\Cluster\TestCase
{
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
        $schema = $this->getSchema();
        $this->assertTrue($this->getSchema()->hasTable('linktest_product'));
        $this->assertTrue($this->getSchema()->hasTable('linktest_product_info'));

        $productTable = $schema->getTable('linktest_product');
        $this->assertInstanceOf('\Model\Cluster\Schema\Table', $productTable);
    }

    /**
     * @group schema
     */
    public function testCreateSchema()
    {
        $this->assertTrue($this->getSchema()->hasTable('product'));
        $this->assertFalse($this->getSchema()->hasTable(null));

        $productTable = $this->getSchema()->getTable('product');
        $this->assertInstanceOf('\Model\Cluster\Schema\Table', $productTable);

        $this->assertNull($this->getSchema()->getTable('not_exists'));
    }
}
