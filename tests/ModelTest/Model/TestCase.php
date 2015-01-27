<?php

namespace ModelTest\Model;
use Model\Generator;
use ModelTest\TestCase as ParentTestCase;

/**
 * Class TestCase
 *
 * @package ModelTest\Model
 * @group Model
 */
abstract class TestCase extends ParentTestCase
{
    /**
     * @var Generator
     */
    protected static $generator;

    protected function setUp()
    {
        parent::setUp();

        $cluster = new \Model\Cluster();
        $schema = $this->getSchema(true);
        $cluster->addSchema($schema);

        /** @var Generator generator */

        if (!self::$generator) {
            self::$generator = new Generator();

            $runString = "--output-dir=" . GENERATE_OUTPUT . ' --db-user=' . DB_USER . ' --db-password=' . DB_PASSWORD . ' --db-schema=' . DB_NAME . ' --db-host=' . DB_HOST;
            //$this->getDb(), $cluster, GENERATE_OUTPUT
            self::$generator->run($runString);
        }
    }

    protected function tearDown()
    {
        $this->getDb()->disconnect();
    }
}