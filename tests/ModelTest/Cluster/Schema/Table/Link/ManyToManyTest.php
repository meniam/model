<?php

namespace ModelTest\Cluster\Schema\Table\Link;
use ModelTest\Cluster\Schema\Table\Link\TestCase as TestCase;

/**
 * Тестирование связи Many To Many
 *
 * @category   ModelTest
 * @package    ModelTest_Schema_Table_Link
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 * @group      ModelTest_Schema_Table_Link_ManyToMany
 */
class ManyToManyTest extends TestCase
{
    /**
     * @var \Model\Cluster\Schema\Table
     */
    protected $_table;

    protected function setUp()
    {
        $this->_table  = $this->getSchema()->getTable('linktest_mtm_first');
    }

    protected function tearDown()
    {
        $this->_table  = null;
    }

    public function testLink()
    {
        $table = $this->getSchema()->getTable('linktest_mtm_first');

        $links = $table->getLink();
        $autoLink = $mixedLink = null;

        foreach ($links as $link) {
            if ($link->getName() == 'ManyToMany___LinktestMtmFirst___LinktestMtmSecond') {
                $autoLink = $link;
            }

            if ($link->getName() == 'ManyToMany___LinktestMtmFirst___TestEntity') {
                $mixedLink = $link;
            }
        }

        $this->assertInstanceOf('Model\Cluster\Schema\Table\Link\ManyToMany', $autoLink);
        $this->assertInstanceOf('Model\Cluster\Schema\Table\Link\ManyToMany', $mixedLink);
    }

    public function testGetLocalForeignEntity()
    {
        $table = $this->getSchema()->getTable('linktest_mtm_first');

        $links = $table->getLink();

        foreach ($links as $link) {
            if ($link->getName() == 'ManyToMany___LinktestMtmFirst___TestEntity') {
                $mixedLink = $link;
            }
        }

        $this->assertEquals('linktest_mtm_first', $mixedLink->getLocalEntity());
        $this->assertEquals('linktestMtmFirst',   $mixedLink->getLocalEntityAsVar());
        $this->assertEquals('LinktestMtmFirst',   $mixedLink->getLocalEntityAsCamelCase());

        $this->assertEquals('test_entity', $mixedLink->getForeignEntity());
        $this->assertEquals('testEntity', $mixedLink->getForeignEntityAsVar());
        $this->assertEquals('TestEntity', $mixedLink->getForeignEntityAsCamelCase());
    }
}

