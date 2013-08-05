<?php

namespace Model\Generator\Part;

use Model\Generator\Log;
use Model\Cluster;
use Model\Cluster\Schema;
use Model\Cluster\Schema\Table;

class Model extends AbstractPart
{
  	public function __construct(Table $table, Cluster $cluster, $outputFilename = null)
	{
		Log::info('Generate part mode ' . $table->getName());

        $this->_table = $table;

        $file = new \Zend\Code\Generator\FileGenerator();
        $this->setFile($file);

        $file->setNamespace('Model');

        $class = new \Zend\Code\Generator\ClassGenerator();
        $file->setClass($class);
        $file->setUse('Model\\Result\\Result');
/*        $file->setUse('Model\\Entity\\' . $table->getNameAsCamelCase() . 'Entity');
        $file->setUse('Model\\Cond\\' . $table->getNameAsCamelCase() . 'Cond');
        $file->setUse('Model\\Collection\\' . $table->getNameAsCamelCase() . 'Collection');*/

		$this->_runPlugins(self::PART_MODEL, self::RUNTIME_PRE);

        $class->setName('Abstract' . $table->getNameAsCamelCase() . 'Model');
        $class->setExtendedClass('\Model\Mysql\AbstractModel');

		$this->_runPlugins(self::PART_MODEL, self::RUNTIME_POST);

        if ($outputFilename) {
            file_put_contents($outputFilename, $file->generate());
        }
    }
}
