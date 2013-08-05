<?php

namespace ModelTest\Db\Mysql;

use Model\Db\Mysql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\TableIdentifier;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Adapter\Platform\Sql92;

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
class SelectTest extends \ModelTest\Db\Mysql\TestCase
{
    /**
     * @var \Model\Db\Mysql\Select
     */
    protected $select;

    public function setUp()
    {
        $this->select = new Select();
    }

    /**
     * @testdox unit test: Test from() returns Select object (is chainable)
     * @covers Model\Db\Select::from
     */
    public function testFrom()
    {
        $select = new Select;
        $return = $select->from('foo', 'bar');
        $this->assertSame($select, $return);

        $return = $select->from('product', 'product_table');
        $this->assertContains('`product_table`', $return->getSql());

        $return = $select->from('products');
        $this->assertContains('`products`', $return->getSql());

        $return = $select->from(new \Zend\Db\Sql\Expression('product'));
        $this->assertContains(' product', $return->getSql());
        $this->assertContains(' * ', $return->getSql());

        return $return;
    }

    /**
     * @testdox unit test: Test where() returns Select object (is chainable)
     * @covers Zend\Db\Sql\Select::where
     */
    public function testWhereReturnsSameSelectObject()
    {
        $select = new Select;
        $this->assertSame($select, $select->where('x = y'));
    }

    /**
     * @testdox unit test: Test where() will accept a string for the predicate to create an expression predicate
     * @covers Zend\Db\Sql\Select::where
     */
    public function testWhereArgument1IsString()
    {
        $select = new Select;
        $select->where('x = y');

        /** @var $where Where */
        $where = $select->getRawState('where');
        $predicates = $where->getPredicates();
        $this->assertEquals(1, count($predicates));
        $this->assertInstanceOf('Zend\Db\Sql\Predicate\Expression', $predicates[0][1]);
        $this->assertEquals(Where::OP_AND, $predicates[0][0]);
        $this->assertEquals('x = y', $predicates[0][1]->getExpression());
    }

    /**
     * @testdox unit test: Test where() will accept an array with a string key (containing ?) used as an expression with placeholder
     * @covers Zend\Db\Sql\Select::where
     */
    public function testWhereArgument1IsAssociativeArrayContainingReplacementCharacter()
    {
        $select = new Select;
        $select->where(array('foo > ?' => 5));

        /** @var $where Where */
        $where = $select->getRawState('where');
        $predicates = $where->getPredicates();
        $this->assertEquals(1, count($predicates));
        $this->assertInstanceOf('Zend\Db\Sql\Predicate\Expression', $predicates[0][1]);
        $this->assertEquals(Where::OP_AND, $predicates[0][0]);
        $this->assertEquals('foo > ?', $predicates[0][1]->getExpression());
        $this->assertEquals(array(5), $predicates[0][1]->getParameters());
    }

    /**
     * @testdox unit test: Test where() will accept any array with string key (without ?) to be used as Operator predicate
     * @covers Zend\Db\Sql\Select::where
     */
    public function testWhereArgument1IsAssociativeArrayNotContainingReplacementCharacter()
    {
        $select = new Select;
        $select->where(array('name' => 'Ralph', 'age' => 33));

        /** @var $where Where */
        $where = $select->getRawState('where');
        $predicates = $where->getPredicates();
        $this->assertEquals(2, count($predicates));

        $this->assertInstanceOf('Zend\Db\Sql\Predicate\Operator', $predicates[0][1]);
        $this->assertEquals(Where::OP_AND, $predicates[0][0]);
        $this->assertEquals('name', $predicates[0][1]->getLeft());
        $this->assertEquals('Ralph', $predicates[0][1]->getRight());

        $this->assertInstanceOf('Zend\Db\Sql\Predicate\Operator', $predicates[1][1]);
        $this->assertEquals(Where::OP_AND, $predicates[1][0]);
        $this->assertEquals('age', $predicates[1][1]->getLeft());
        $this->assertEquals(33, $predicates[1][1]->getRight());
    }

    /**
     * @testdox unit test: Test where() will accept an indexed array to be used by joining string expressions
     * @covers Zend\Db\Sql\Select::where
     */
    public function testWhereArgument1IsIndexedArray()
    {
        $select = new Select;
        $select->where(array('name = "Ralph"'));

        /** @var $where Where */
        $where = $select->getRawState('where');
        $predicates = $where->getPredicates();
        $this->assertEquals(1, count($predicates));

        $this->assertInstanceOf('Zend\Db\Sql\Predicate\Expression', $predicates[0][1]);
        $this->assertEquals(Where::OP_AND, $predicates[0][0]);
        $this->assertEquals('name = "Ralph"', $predicates[0][1]->getExpression());
    }

    /**
     * @testdox unit test: Test where() will accept an indexed array to be used by joining string expressions, combined by OR
     * @covers Zend\Db\Sql\Select::where
     */
    public function testWhereArgument1IsIndexedArrayArgument2IsOr()
    {
        $select = new Select;
        $select->where(array('name = "Ralph"'), Where::OP_OR);

        /** @var $where Where */
        $where = $select->getRawState('where');
        $predicates = $where->getPredicates();
        $this->assertEquals(1, count($predicates));

        $this->assertInstanceOf('Zend\Db\Sql\Predicate\Expression', $predicates[0][1]);
        $this->assertEquals(Where::OP_OR, $predicates[0][0]);
        $this->assertEquals('name = "Ralph"', $predicates[0][1]->getExpression());
    }

    /**
     * @testdox unit test: Test where() will accept a closure to be executed with Where object as argument
     * @covers Zend\Db\Sql\Select::where
     */
    public function testWhereArgument1IsClosure()
    {
        $select = new Select;
        $where = $select->getRawState('where');

        $test = $this;
        $select->where(function ($what) use ($test, $where) {
            $test->assertSame($where, $what);
        });
    }

    /**
     * @testdox unit test: Test where() will accept a Where object
     * @covers Zend\Db\Sql\Select::where
     */
    public function testWhereArgument1IsWhereObject()
    {
        $select = new Select;
        $select->where($newWhere = new Where);
        $this->assertSame($newWhere, $select->getRawState('where'));
    }

    /**
     * @author Rob Allen
     * @testdox unit test: Test order()
     * @covers Zend\Db\Sql\Select::order
     */
    public function testOrder()
    {
        $select = new Select;
        $return = $select->order('id DESC');
        $this->assertSame($select, $return); // test fluent interface
        $this->assertEquals(array('id DESC'), $select->getRawState('order'));

        $select = new Select;
        $select->order('id DESC')
            ->order('name ASC, age DESC');
        $this->assertEquals(array('id DESC', 'name ASC', 'age DESC'), $select->getRawState('order'));

        $select = new Select;
        $select->order(array('name ASC', 'age DESC'));
        $this->assertEquals(array('name ASC', 'age DESC'), $select->getRawState('order'));
    }

    /**
     * @testdox unit test: Test join() returns same Select object (is chainable)
     * @covers Zend\Db\Sql\Select::having
     */
    public function testHaving()
    {
        $select = new Select;
        $return = $select->having(array('x = ?' => 5));
        $this->assertSame($select, $return);
        return $return;
    }

    /**
     * @testdox unit test: Test getRawState() returns information populated via having()
     * @covers Zend\Db\Sql\Select::getRawState
     * @depends testHaving
     */
    public function testGetRawStateViaHaving(Select $select)
    {
        $this->assertInstanceOf('Zend\Db\Sql\Having', $select->getRawState('having'));
    }

    /**
     * @testdox unit test: Test join() returns same Select object (is chainable)
     * @covers Zend\Db\Sql\Select::group
     */
    public function testGroup()
    {
        $select = new Select;
        $return = $select->group(array('col1', 'col2'));
        $this->assertSame($select, $return);
        return $return;
    }

    /**
     * @testdox unit test: Test getRawState() returns information populated via group()
     * @covers Zend\Db\Sql\Select::getRawState
     * @depends testGroup
     */
    public function testGetRawStateViaGroup(Select $select)
    {
        $this->assertEquals(
            array('col1', 'col2'),
            $select->getRawState('group')
        );
    }

    /**
     * @testdox unit test: Test __get() returns expected objects magically
     * @covers Zend\Db\Sql\Select::__get
     */
    public function test__get()
    {
        $select = new Select;
        $this->assertInstanceOf('Zend\Db\Sql\Where', $select->where);
        $this->assertInstanceOf('Zend\Db\Sql\Having', $select->having);
    }


    /**
     * @testdox unit test: Test __clone() will clone the where object so that this select can be used in multiple contexts
     * @covers Zend\Db\Sql\Select::__clone
     */
    public function test__clone()
    {
        $select = new Select;
        $select1 = clone $select;
        $select1->where('id = foo');
        $select1->having('id = foo');

        $this->assertEquals(0, $select->where->count());
        $this->assertEquals(1, $select1->where->count());

        $this->assertEquals(0, $select->having->count());
        $this->assertEquals(1, $select1->having->count());
    }


    public function testGetSql()
    {
        $subselect= new \Model\Db\Mysql\Select();
        $subselect->from('product');

        $this->getSelect()->from(new \Zend\Db\Sql\Expression('test'));
        $this->getSelect()->columns(array('id', 'test' => new \Zend\Db\Sql\Expression('Count(*)')));
        $this->getSelect()->join('test2', 'test2.id = test.id', array('id'), \Model\Db\Mysql\Select::JOIN_LEFT);
        $this->getSelect()->join('test3', 'test2.id = test.id', array('id'), \Model\Db\Mysql\Select::JOIN_LEFT);
        $this->getSelect()->where(array('id' => null));

    }

    /**
     * @return \Model\Db\Mysql\Select
     */
    public function getSelect()
    {
        return $this->select;
    }
}

