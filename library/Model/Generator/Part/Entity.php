<?php

namespace Model\Generator\Part;

use Model\Generator\Log;
use Model\Cluster\Schema;
use Model\Cluster\Schema\Table;

class Entity extends AbstractPart
{
    public function __construct(\Model\Cluster\Schema\Table $table, \Model\Cluster $cluster, $outputFilename = null)
	{
		Log::info('Generate part entity ' . $table->getName());

        $this->_table = $table;

        $file = new \Zend\Code\Generator\FileGenerator();
        $this->setFile($file);

        $class = new \Zend\Code\Generator\ClassGenerator();
        $file->setClass($class);

		$this->_runPlugins(self::PART_ENTITY, self::RUNTIME_PRE);

        $class->setNamespaceName('Model\Entity');
        $class->setName('Abstract' . $table->getNameAsCamelCase() . 'Entity');
        $class->setExtendedClass('AbstractEntity');
        $class->setAbstract(true);


        $this->_runPlugins(self::PART_ENTITY, self::RUNTIME_POST);

        if ($outputFilename) {
            file_put_contents($outputFilename, $file->generate());
        }
    }
}
