<?php

namespace Model\Generator\Part\Plugin\Model;

use Model\Generator\Part\PartInterface;
use Model\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\AbstractMemberGenerator;

class GetInstance extends AbstractModel
{
	public function __construct()
	{
 		$this->_setName('GetInstance');
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
         * @var $file \Model\Code\Generator\FileGenerator
         */
        $file = $part->getFile();


        $tableNameAsCamelCase = $part->getTable()->getNameAsCamelCase();

        $tags = array(
            array(
                'name'        => 'return',
                'description' => '\\Model\\' . $tableNameAsCamelCase . 'Model экземпляр модели',
            ),
        );

        $docblock = new DocBlockGenerator('Получить экземпляр модели ' . $tableNameAsCamelCase);
        $docblock->setTags($tags);

        $method = new MethodGenerator();
        $method->setName('getInstance');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setStatic(true);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
return parent::getInstance();
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }


}