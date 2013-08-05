<?php

namespace Model\Generator\Part\Plugin\Model;

use Model\Generator\Part\PartInterface;
use Model\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\AbstractMemberGenerator;

class Construct extends AbstractModel
{
    public function __construct()
    {
        $this->_setName('Construct');
    }

    public function preRun(PartInterface $part)
    {
    }

    public function postRun(PartInterface $part)
    {
        /**
         * @var $part \Model\Generator\Part\Entity
         */

        /**
         * @var $file \Zend\Code\Generator\FileGenerator
         */
        $file = $part->getFile();


        $tableName = $part->getTable()->getName();

        $docblock = new DocBlockGenerator('Конструктор');

        $method = new MethodGenerator();
        $method->setName('__construct');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
\$this->setName('{$tableName}');
\$this->setDbAdapterName('db');
parent::__construct();
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }


}