<?php

namespace Model\Config;

class Config implements \ArrayAccess
{
    protected $parameters = array();
    protected $flattenedParameters = array();
    protected $isFlattened = false;

    /**
     * Construct a new configuration container
     *
     * @param array $parameters
     */
    public function __construct(array $parameters = array())
    {
        $this->parameters = $parameters;
    }

    /**
     * @see       http://www.php.net/ArrayAccess
     *
     * @param integer $offset
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->parameters);
    }

    /**
     * @see       http://www.php.net/ArrayAccess
     *
     * @param integer $offset
     * @param mixed   $value
     */
    public function offsetSet($offset, $value)
    {
        $this->parameters[$offset] = $value;
        $this->isFlattened = false;
    }

    /**
     * @see       http://www.php.net/ArrayAccess
     *
     * @param integer $offset
     *
     * @return array
     */
    public function offsetGet($offset)
    {
        return $this->parameters[$offset];
    }

    /**
     * @see       http://www.php.net/ArrayAccess
     *
     * @param integer $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->parameters[$offset]);
        $this->isFlattened = false;
    }

    /**
     * Get a value from the container, using a namespaced key.
     * If the specified value is supposed to be an array, the actual return value will be null.
     * Examples:
     * <code>
     *   $c['foo'] = 'bar';
     *   echo $c->getParameter('foo'); => 'bar'
     *   $c['foo1'] = array('foo2' => 'bar');
     *   echo $c->getParameter('foo1'); => null
     *   echo $c->getParameter('foo1.foo2'); => 'bar'
     * </code>
     *
     * @param string $name    Parameter name
     * @param mixed  $default Default value to be used if the requested value is not found
     *
     * @return mixed Parameter value or the default
     */
    public function getParameter($name, $default = null)
    {
        $flattenedParameters = $this->getFlattenedParameters();
        if (isset($flattenedParameters[$name])) {
            return $flattenedParameters[$name];
        }

        return $default;
    }

    /**
     * Store a value to the container. Accept scalar and array values.
     * Examples:
     * <code>
     *   $c->setParameter('foo', 'bar');
     *   echo $c['foo']; => 'bar'
     *   $c->setParameter('foo1.foo2', 'bar');
     *   print_r($c['foo1']); => array('foo2' => 'bar')
     * </code>
     *
     * @param string  $name              Configuration item name (name.space.name)
     * @param mixed   $value             Value to be stored
     * @param Boolean $autoFlattenArrays
     */
    public function setParameter($name, $value, $autoFlattenArrays = true)
    {
        $param = &$this->parameters;
        $parts = explode('.', $name); //name.space.name
        foreach ($parts as $part) {
            $param = &$param[$part];
        }
        $param = $value;
        if (is_array($value) && $autoFlattenArrays) {
            // The list will need to be re-flattened.
            $this->isFlattened = false;
        } else {
            $this->flattenedParameters[$name] = $value;
        }
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return (array)$this->parameters;
    }

    /**
     * @return array
     */
    public function getFlattenedParameters()
    {
        if (!$this->isFlattened) {
            $this->flattenParameters();
            $this->isFlattened = true;
        }

        return $this->flattenedParameters;
    }

    protected function flattenParameters()
    {
        $result = array();
        $it = new ConfigIterator(new \RecursiveArrayIterator($this->parameters), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $key => $value) {
            $ns = $it->getDepth() ? $it->getNamespace() . '.' . $key : $key;
            if ($it->getNodeType() == ConfigIterator::NODE_ITEM) {
                $result[$ns] = $value;
            }
        }
        $this->flattenedParameters = array_merge($this->flattenedParameters, $result);
    }

}