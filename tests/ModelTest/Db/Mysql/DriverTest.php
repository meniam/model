<?php

namespace ModelTest\Db\Mysql;

/**
 * Short description for class
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 * @group      ModelTest_Db_Driver_Mysql
 */
class DriverTest extends \ModelTest\Db\Mysql\TestCase
{
    /**
     * @var \Model\Db\Mysql\Driver
     */
    protected $driver;


    private $connectData = array(
            'driver' => 'PDO_MYSQL',
            'database' => 'model_test',
            'username' => 'root',
            'password' => 'macbook'
         );

    public function setUp()
    {
        require_once __DIR__ . '/../../_files/TestCollection.php';

        $this->driver = new \Model\Db\Mysql\Driver($this->connectData);
    }

    public function testQuote()
    {
       /* $this->assertEquals('\'product\'', $this->getDriver()->quote('product'));
        $this->assertEquals('\'pro\\duct\'', $this->getDriver()->quote('pro\\duct'));
        $this->assertEquals('\'pro\\\'duct\'', $this->getDriver()->quote('pro\'duct'));

        $this->assertEquals('\'pro\\\'duct\'', $this->getDriver()->quote(array('pro\'duct')));
        $this->assertEquals('\'a\', \'b\'', $this->getDriver()->quote(array('a', 'b')));*/
    }

    /**
     * @expectedException \PDOException
     */
    public function testQueryException()
    {
        $this->getDriver()->query('SELECT ids FROM product WHERE id = -100000');
    }

    public function testFetchAll()
    {
        $result = $this->getDriver()->fetchAll('SELECT id FROM product ORDER BY id LIMIT 1');
        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);

        $this->assertEquals(array(array('id' => 2)), $result);

        $result = $this->getDriver()->fetchAll('SELECT id FROM product WHERE id = -100000');
        $this->assertInternalType('array', $result);
    }

    public function testFetchRow()
    {
        $result = $this->getDriver()->fetchRow('SELECT id FROM product ORDER BY id LIMIT 1');
        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);

        $this->assertEquals(array('id' => 2), $result);

        $result = $this->getDriver()->fetchRow('SELECT id FROM product WHERE id = -100000');
        $this->assertInternalType('array', $result);

    }

    public function testFetchOne()
    {
        $result = $this->getDriver()->fetchOne('SELECT 1+3, 1+1 test FROM product ORDER BY id LIMIT 1');
        $this->assertEquals(4, $result);
    }

    public function testFetchPairs()
    {
        $result = $this->getDriver()->fetchPairs('SELECT id, 1+1 test FROM product ORDER BY id LIMIT 1');
        $this->assertEquals(array(2 => 2), $result);
    }

    /**
     * @group performance
     */
    public function testPerformance()
    {

        $zendAdapter = new \Zend\Db\Adapter\Adapter($this->connectData);
        $sql = "SELECT * FROM PRODUCT LIMIT 1000";

        $start = microtime(true);
        for ($i=0; $i<10; $i++) {
            $this->getDriver()->fetchAll($sql);
        }

        $modelTime = (microtime(true) - $start);

        $start = microtime(true);
        for ($i=0; $i<10; $i++) {

            /** @var $statement \Zend\Db\Adapter\Driver\StatementInterface */
            $statement = $zendAdapter->query($sql);

            /* @var $results \Zend\Db\ResultSet\ResultSet */
            $result = $statement->execute();

            $rows = array();
            while($row = $result->next()) {
                $rows[] = $row;
            }
        }
        $zendTime = (microtime(true) - $start);

        echo "ZendTime: " . $zendTime . ' > ModelTime: ' . $modelTime . ' = ' . ($zendTime - $modelTime) . ' / ' . round($zendTime / $modelTime * 100) . '%';

        $this->assertLessThan($zendTime, $modelTime);
    }

    /**
     * @return \Model\Db\Driver\Mysql
     */
    public function getDriver()
    {
        return $this->driver;
    }
}

