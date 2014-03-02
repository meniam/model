<?php

namespace Model\Generator\Part;

use Model\Generator\Log;
use Model\Cluster\Schema;
use Model\Cluster\Schema\Table;

class Collection extends AbstractPart
{
    public function __construct(\Model\Cluster\Schema\Table $table, \Model\Cluster $cluster, $outputFilename = null)
	{
		Log::debug('Generate part list ' . $table->getName());

        $this->_table = $table;

        $file = new \Model\Code\Generator\FileGenerator();
        $this->setFile($file);

        $class = new \Zend\Code\Generator\ClassGenerator();
        $file->setClass($class);
        $file->setNamespace('Model\\Collection');
        //$file->setUse('Model\ResultList');

		$this->_runPlugins(self::PART_COLLECTION, self::RUNTIME_PRE);

        $class->setName('Abstract' . $table->getNameAsCamelCase() . 'Collection');
        $class->setExtendedClass('AbstractCollection');
        $class->setAbstract(true);

        $this->_runPlugins(self::PART_COLLECTION, self::RUNTIME_POST);

        if ($outputFilename) {
            file_put_contents($outputFilename, $file->generate());
        }
    }
}
