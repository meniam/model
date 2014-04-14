<?php

namespace ModelTest;

use Model\Cluster\Schema;
use Model\Cluster;
use Model\Generator;

/**
 * Тестирование генератора
 *
 * @category   ModelTest
 * @package    ModelTest_Generator
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 * @group      ModelTest_Generator
 */
class GeneratorTest extends \ModelTest\TestCase
{
    /**
     * @var \Model\Generator
     */
    private $generator;

    protected function setUp()
    {
        $cluster = new Cluster();
        $schema = $this->getSchema();
        $cluster->addSchema($schema);

        $this->generator = new Generator($this->getDb(), $cluster, '/tmp/generate');
    }

    protected function tearDown()
    {
         $this->generator = null;
    }

    public function testGetSchema()
    {
        $this->assertInstanceOf('Model\Generator', $this->generator);

        $this->assertInstanceOf('Model\Cluster', $this->generator->getCluster());
        $this->assertTrue($this->generator->getCluster()->hasSchema('model_test'));
    }
}
