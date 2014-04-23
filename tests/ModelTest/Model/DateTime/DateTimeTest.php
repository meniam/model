<?php

namespace ModelTest\Model\DateTime;

use Model\DateTime\DateTime;

/**
 * Model link method's tests
 *
 * @package ModelTest
 * @group Model
 */
class DateTimeTest extends \ModelTest\Model\DateTime\TestCase
{
    /**
     * @group datetime
     */
    public function testDayOff()
    {
        $test = new DateTime('1982-06-01');

        $this->assertFalse($test->isDayOff());
    }
}
