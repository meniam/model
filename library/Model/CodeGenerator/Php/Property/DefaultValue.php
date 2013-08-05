<?php

class Model_CodeGenerator_Php_Property_DefaultValue extends Zend_CodeGenerator_Php_Property_DefaultValue
{
    public function generate() 
    {
        $result = parent::generate();
        $result = preg_replace('#\s+\)\;$#', ');', $result);
        $result = preg_replace('#^array\(\s+#', 'array(', $result);
        return $result;
    }
}