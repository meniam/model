<?php

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
	protected function setUp()
	{ }

    /**
     * @param $class
     * @param $methodName
     * @return \ReflectionMethod
     */
    protected static function getMethod($class, $methodName)
    {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }

    protected function _testParamSettersAndGetters($object, $name, $elementType = null)
    {
        if (!is_object($object)) {
            return;
        }

        $nameAsCameCase = implode('', array_map('ucfirst', explode('_', $name)));
        $objectClass   = get_class($object);
        $objectMethods = get_class_methods($objectClass);

        $setMethodName = 'set' . $nameAsCameCase;
        if (in_array($setMethodName, $objectMethods)) {
            $value = $this->getRandomValue($elementType);

            $this->assertInstanceOf($objectClass, $object->$setMethodName('name', $value));

            $getMethodName = 'get' . $nameAsCameCase;
            if (in_array($getMethodName, $objectMethods)) {
                $this->assertEquals($value, $object->$getMethodName('name'));
            }
        }

        $getMethodName = 'get' . $nameAsCameCase;
        $getExists = (in_array($getMethodName, $objectMethods));

        if (!$getExists) {
            return;
        }

        $getsMethodName = 'get' . $nameAsCameCase . 's';
        if (in_array($getsMethodName, $objectMethods)) {
            $this->assertInternalType('array', $object->$getsMethodName());
        }
    }

    protected function _testSettersAndGetters($object, $name, $elementType = null, $allowDefault = false)
    {
        if (!is_object($object)) {
            return;
        }

        $nameAsCameCase = implode('', array_map('ucfirst', explode('_', $name)));
        $objectClass   = get_class($object);
        $objectMethods = get_class_methods($objectClass);

        $setMethodName = 'set' . $nameAsCameCase;

        if (in_array($setMethodName, $objectMethods)) {
            $value = $this->getRandomValue($elementType);

            $this->assertInstanceOf($objectClass, $object->$setMethodName($value));

            $getMethodName = 'get' . $nameAsCameCase;
            if (in_array($getMethodName, $objectMethods)) {
                $this->assertEquals($value, $object->$getMethodName());

                if ($elementType == 'bool') {
                    $this->assertInternalType('bool', $object->$setMethodName('on')->$getMethodName());
                    $this->assertEquals(true, $object->$setMethodName('on')->$getMethodName());
                    $this->assertEquals(true, $object->$setMethodName('off')->$getMethodName());
                    $this->assertEquals(true, $object->$setMethodName('-1')->$getMethodName());
                    $this->assertEquals(false, $object->$setMethodName('0')->$getMethodName());
                    $this->assertEquals(true, $object->$setMethodName('1')->$getMethodName());

                    if ($allowDefault) {
                        $object->$setMethodName(null);
                        $this->assertEquals(true, $object->$getMethodName(true));
                    }
                }
            }
        }
    }


    public function getRandomValue($type)
    {
        switch ($type) {
            case 'boolean':
            case 'bool':
                return (bool)mt_rand(0,1);
                break;
            default:
                return uniqid('', true);
        }
    }
}
