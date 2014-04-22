<?php

namespace ModelTest\Model\DateTime;
use ModelTest\TestCase as ParentTestCase;

/**
 * Class TestCase
 *
 * @package ModelTest\Model
 * @group Model
 */
abstract class TestCase extends ParentTestCase
{
    protected function tearDown()
    {
        $this->getDb()->disconnect();
    }
}