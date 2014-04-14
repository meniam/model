<?php

namespace Model\Generator\Part;

use Model\Cluster;
use Model\Code\Generator\FileGenerator;
use Model\Generator\Log;
use Model\Cluster\Schema;
use Model\Cluster\Schema\Table;
use Zend\Code\Generator\ClassGenerator;

class Collection extends AbstractPart
{
    public function __construct(Table $table, Cluster $cluster, $outputFilename = null)
	{
		Log::debug('Generate part list ' . $table->getName());

        $this->_table = $table;

        $file = new FileGenerator();
        $this->setFile($file);

        $class = new ClassGenerator();
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
