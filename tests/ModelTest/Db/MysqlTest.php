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
 * @group      ModelTest_Db_Mysql
 */
class MysqlTest extends \ModelTest\Db\TestCase
{

    public function setUp()
    {
    }

    public function testBuildSql()
    {
        $sql = "SELECT * FROM test WHERE test = :test";
        $expected = "SELECT * FROM test WHERE test = 1";
        $bindParams = array(':test' => 1);

        $this->assertEquals($expected, $this->getDb()->buildSql($sql, $bindParams));
    }

    public function testProfiler()
    {
        $this->getDb()->setEnableProfiler(true)->query('SELECT * FROM product LIMIT 1');

        $profiler = $this->getDb()->getProfiler();
        $this->assertArrayHasKey(0, $profiler);
        $this->assertArrayHasKey('query', $profiler[0]);
    }

    public function testPrepareBindParams()
    {
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

