<?php

namespace ModelTest\Model\Result;

/**
 * Model link method's tests
 *
 * @package ModelTest
 * @group Model
 */
class ResultTest extends \ModelTest\Model\Result\TestCase
{
    /**
     * @group result
     */
    public function testResultInstance()
    {
        $result1 = new \Model\Result\Result;
        $result = new \Model\Result\Result;
        $result->addChild('name', $result1);

        $this->assertInstanceOf('Model\Result\Result', $result);
    }
}
