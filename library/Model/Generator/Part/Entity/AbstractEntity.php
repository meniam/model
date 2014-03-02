<?php

namespace Model\Generator\Part\Entity;

use Model\Generator\Part\AbstractPart  as AbstractPart;
use Model\Generator\Part\PartInterface as PartInterface;
use Model\Generator\Log                as Log;
use Model\Cluster\Schema;
use Model\Cluster\Schema\Table;

class AbstractEntity extends AbstractPart implements PartInterface
{
  	public function __construct(Table $table, Schema $cluster, $outputFilename = null)
	{
		Log::info('Generate part entity ' . $table->getName());
        $this->_table = $table;

        $file  = new \Model\Code\Generator\FileGenerator();
        $class = new  \Zend\Code\Generator\ClassGenerator();
        $file->setClass($class);
        $this->setFile($file);

		$this->_runPlugins(self::PART_ENTITY_ABSTRACT, self::RUNTIME_PRE);

        $class->setName($table->getNameAsCamelCase() . 'EntityAbstract');
        $class->setExtendedClass('\Model\Entity');

		$this->_runPlugins(self::PART_ENTITY_ABSTRACT, self::RUNTIME_POST);

        if ($outputFilename) {
            file_put_contents($outputFilename, $file->generate());
        }
    }
}
