<?php

namespace Model\Generator\Part\Plugin\Cond;
use Model\Generator\Part\PartInterface;

class SetupEntity extends AbstractCond
{
    public function __construct()
    {
        $this->_setName('SetupEntity');
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
        $tableNameAsCamelCase = $part->getTable()->getNameAsCamelCase();

        $tags = array(
            array(
                'name'        => 'return',
                'description' => 'void',
            ),
        );

        $docblock = new \Zend\Code\Generator\DocBlockGenerator('Настраиваем текущую сущность');
        $docblock->setTags($tags);

        $method = new \Zend\Code\Generator\MethodGenerator();
        $method->setName('setupEntity');
        $method->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
\$this->setName('$tableName');
\$this->setEntityName('$tableName');
\$this->setEntityClassName('\\Model\\Entity\\{$tableNameAsCamelCase}Entity');
\$this->setCollectionClassName('\\Model\\Collection\\{$tableNameAsCamelCase}Collection');
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }
}