<?php

namespace ModelTest\Schema\Db\Adapter;


/**
 * Short description for class
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 * @group      ModelTest_Db_Adapter_Mysql
 */
class MysqlTest extends \ModelTest\Db\Adapter\TestCase
{
    /**
     * @var \Model\TagModel
     */
    protected $_model;

    public function setUp()
    {
        require_once __DIR__ . '/../../_files/ProductCond.php';
        //require_once __DIR__ . '/../../_files/ProductModel.php';
        //$this->_model = \Model\TagModel::getInstance();
    }

    public function testFetchAll()
    {
    }

}

