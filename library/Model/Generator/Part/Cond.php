<?php

namespace Model\Generator\Part;

use Model\Generator\Log;
use Model\Cluster\Schema;
use Model\Cluster\Schema\Table;

use Model\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;

class Cond extends AbstractPart
{
  	public function __construct(\Model\Cluster\Schema\Table $table,  \Model\Cluster $cluster, $outputFilename = null)
	{
        $this->_table = $table;
        $this->outputFilename = $outputFilename;
    }

    public function generate(array $options = array())
    {
        $this->setOptions($options);
        $table = $this->getTable();

        $alias = $this->getOption('alias', null);
        $aliasAsCamelCase = implode('', array_map('ucfirst', explode('_', $alias)));

        if ($alias) {
            $className = 'Abstract' . $aliasAsCamelCase . 'Cond';
            $extendedClassName = 'Abstract' . $table->getNameAsCamelCase() . 'Cond';
        } else {
            $className = 'Abstract' . $table->getNameAsCamelCase() . 'Cond';

            if ($table->getColumn('parent_id')) {
                $extendedClassName = 'TreeCond';
            } else {
                $extendedClassName = 'AbstractCond';
            }
        }

        Log::info('Generate part cond ' . ($alias ? $alias : $table->getName()));

        $file = new FileGenerator();
        $this->setFile($file);

        $class = new ClassGenerator();
        $file->setClass($class);

        $this->_runPlugins(self::PART_COND, self::RUNTIME_PRE);

        $class->setNamespaceName('Model\Cond');
        $class->setName($className);
        $class->setExtendedClass($extendedClassName);

        $this->_runPlugins(self::PART_COND, self::RUNTIME_POST);

        if ($filename = $this->getOutputFilename()) {
            file_put_contents($filename, $file->generate());
        }
    }
}
