<?php

namespace ModelTest\Db;

use \Model\Db\Select;
use \Model\Db\Expr;

/**
 * Short description for class
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 * @group      ModelTest_Db_Select
 */
class SelectTest extends \ModelTest\Db\TestCase
{

    public function setUp()
    {
    }

    public function testInstance()
    {
        $select = new Select($this->getDb());
    }

    public function testFrom()
    {
        $select = new Select($this->getDb());
        $this->assertEquals($select->from('test')->__toString(), 'SELECT test.* FROM test');

        $select = new Select($this->getDb());
        $this->assertEquals($select->from(array('z' => 'test'))->__toString(), 'SELECT z.* FROM test AS z');

        $select = new Select($this->getDb());
        $this->assertEquals($select->from(array('z' => new Expr('(a)')))->__toString(), 'SELECT z.* FROM (a) AS z');
    }

    public function testQuery()
    {
        $db = $this->getDb();

        $select = new Select($db);

        $db->setEnableProfiler(true);
        $stmt = $select->from('product')->limit(1)->query();
        $this->assertInstanceOf('PDOStatement', $stmt);

        $profiler = $db->getProfiler();
        $lastQuery = end($profiler);
        $this->assertEquals('SELECT product.* FROM product LIMIT 1', $lastQuery['query']);
    }

    public function testWhere()
    {
        /*$select = new Select($this->getDb());
        $select->from('product')->where('product_type_id IN (:test)', array(':test' => array('a\'', 'b') ));
        $select->query();
        echo $select;*/

    }

    public function testPrepareBindParams()
    {
        $select = new Select($this->getDb());

        $result = $this->getDb()->prepareBindParams(array(':test' => 1));
        $this->assertEquals(array(':test' => 1), $result);

        $result = $this->getDb()->prepareBindParams(array(':test' => array('a\'b')));
        $this->assertEquals(array(':test' => '\'a\\\'b\''), $result);

        $result = $this->getDb()->prepareBindParams(array(':test' => true));
        $this->assertEquals(array(':test' => 1), $result);

        $result = $this->getDb()->prepareBindParams(array(':test' => false));
        $this->assertEquals(array(':test' => 0), $result);
    }

}

