<?php

class Model_CodeGenerator_Php_Docblock_Tag_Return extends Zend_CodeGenerator_Php_Docblock_Tag_Return
{
    
    /**
     *
     * @param string $datatype Return type
     * @param string $decsription 
     * @return Model_CodeGenerator_Php_Docblock_Tag_Return
     */
    public function __construct($datatype = array(), $description = null)
    {
        if (is_scalar($datatype)) {
            $datatype = array('datatype' => $datatype, 'description' => $description);
        }
        
        return parent::__construct($datatype);
    }
}