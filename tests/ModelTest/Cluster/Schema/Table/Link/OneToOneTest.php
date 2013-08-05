<?php

namespace ModelTest\Cluster\Schema\Table\Link;
use ModelTest\Cluster\Schema\Table\Link\TestCase as TestCase;

/**
 * Тестирование связи One To One
 *
 * @category   ModelTest
 * @package    ModelTest_Schema_Table_Link
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 * @group      ModelTest_Schema_Table_Link_OneToOne
 */
class OneToOneTest extends TestCase
{
    /**
     * @var \Model\Cluster\Schema\Table
     */
    protected $_table;

    protected function setUp()
    {
        $this->_table  = $this->getSchema()->getTable('linktest_oto_first');
    }

    protected function tearDown()
    {
        $this->_table  = null;
    }

    public function testLink()
    {
        $table = $this->getSchema()->getTable('linktest_oto_first');

        $links = $table->getLink();
        $link = reset($links);
        $this->assertEquals(\Model\Cluster\Schema\Table\Link\AbstractLink::LINK_TYPE_ONE_TO_ONE, $link->getLinkType());
        $this->assertEquals('OneToOne___LinktestOtoFirstAlias___LinktestOtoSecond', $link->getName());

        $table = $this->getSchema()->getTable('linktest_oto_second');

        $links = $table->getLink();

        $link = reset($links);
        $this->assertEquals(\Model\Cluster\Schema\Table\Link\AbstractLink::LINK_TYPE_ONE_TO_ONE, $link->getLinkType());
        $this->assertEquals('OneToOne___LinktestOtoSecond___LinktestOtoFirstAlias', $link->getName());
    }
}

