<?php

namespace Model\Generator\Part\Plugin\Model;
use Model\Generator\Part\PartInterface;

class Add extends AbstractModel
{
	public function __construct()
	{
 		$this->_setName('add');
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
        $tableNameAsVar       = $part->getTable()->getNameAsVar();
        //$tableComment = $part->getTable()->getComment();

        $tags = array(
            array(
                'name'        => 'param',
                'description' => 'array|' .  $tableNameAsCamelCase . 'Model $' . $tableNameAsVar
            ),

            array(
                'name'        => 'return',
                'description' => '\\Model\\Entity\\' . $tableNameAsCamelCase,
            ),
        );

        $docblock = new \Zend\Code\Generator\DocBlockGenerator('Добавить ' . $tableNameAsCamelCase);
        $docblock->setTags($tags);

        $method = new \Zend\Code\Generator\MethodGenerator();
        $method->setName('add' . $tableNameAsCamelCase);
        $method->setParameter(new \Zend\Code\Generator\ParameterGenerator($tableNameAsVar, 'mixed'));

        $method->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
\${$tableNameAsVar}Id = null;
\$result = ModelResult();
\${$tableNameAsVar} = new \${$tableNameAsVar}Entity(\${$tableNameAsVar});
\${$tableNameAsVar}Data = \${$tableNameAsVar}->toArray();

// Фильтруем входные данные
\${$tableNameAsVar}Data = \$this->addFilter(\${$tableNameAsVar}Data);

// Валидируем данные
\$validator = \$this->addValidate(\${$tableNameAsVar}Data);
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }
}