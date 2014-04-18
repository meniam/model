<?php

namespace Model\Generator\Part;

use Model\Cluster;
use Model\Code\Generator\FileGenerator;
use Model\Generator\Log;
use Model\Cluster\Schema;
use Model\Cluster\Schema\Table;
use Zend\Code\Generator\ClassGenerator;

class Entity extends AbstractPart
{
  	public function __construct(Table $table, Cluster $cluster, $outputFilename = null, array $options = array())
	{
        if (!empty($options)) {
            $this->setOptions($options);
        }

        Log::info('Generate part entity ' . $table->getName());

        $this->_table = $table;

        $file = new FileGenerator();
        $this->setFile($file);
        $file->setNamespace('Model\\Entity');

        $class = new ClassGenerator();
        $file->setClass($class);

        $file->addUse('Model\\Result\\Result');
        $file->addUse('Model\\Entity\\' . $table->getNameAsCamelCase() . 'Entity');
        $file->addUse('Model\\Cond\\' . $table->getNameAsCamelCase() . 'Cond');
        $file->addUse('Model\\Collection\\' . $table->getNameAsCamelCase() . 'Collection');

		$this->_runPlugins(self::PART_ENTITY, self::RUNTIME_PRE);

        $class->setName('Abstract' . $table->getNameAsCamelCase() . 'Entity');
        $class->setExtendedClass('AbstractEntity');
        $class->setAbstract(true);

        $this->_runPlugins(self::PART_ENTITY, self::RUNTIME_POST);

        if ($outputFilename) {
            file_put_contents($outputFilename, $file->generate());
        }
    }
}
