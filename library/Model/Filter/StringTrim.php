<?php

namespace Model\Filter;

class StringTrim extends AbstractFilter
{
    /**
     * Defined by Zend\Filter\FilterInterface
     *
     * Returns the string $value with characters stripped from the beginning and end
     *
     * @param  string $value
     * @return string
     */
    public function filter($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $value = (string) $value;

        return $this->unicodeTrim($value);
    }
    /**
     * Unicode aware trim method
     * Fixes a PHP problem
     *
     * @param string $value
     * @param string $charlist
     * @return string
     */
    protected function unicodeTrim($value, $charlist = null)
    {
        return ($charlist === null) ? trim($value) : trim($value, $charlist);
    }
}
