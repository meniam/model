<?php

namespace Model\Generator\Part;

use Model\Generator\Log;
use Model\Cluster;
use Model\Cluster\Schema;
use Model\Cluster\Schema\Table;

class FrontModel extends AbstractPart
{
    public function __construct(Table $table, Cluster $cluster, $outputFilename = null)
    {
        Log::info('Generate part front model ' . $table->getName());

        $this->_table = $table;

        $file = new \Zend\Code\Generator\FileGenerator();
        $this->setFile($file);

        $class = new \Zend\Code\Generator\ClassGenerator();
        $file->setClass($class);

        $this->_runPlugins(self::PART_MODEL, self::RUNTIME_PRE);

        $class->setNamespaceName('Model');
        $class->setName($table->getNameAsCamelCase() . 'Model');
        $class->setExtendedClass('Abstract' . $table->getNameAsCamelCase() . 'Model');

        $this->_runPlugins(self::PART_FRONT_MODEL, self::RUNTIME_POST);

        if ($outputFilename) {
            file_put_contents($outputFilename, $file->generate());
        }
    }
}
