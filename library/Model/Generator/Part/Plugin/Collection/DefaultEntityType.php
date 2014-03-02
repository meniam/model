<?php

namespace Model\Generator\Part\Plugin\Collection;
use Model\Generator\Part\PartInterface;

class DefaultEntityType extends AbstractCollection
{
	public function __construct()
	{
 		$this->_setName('DefaultEntityType');
	}

    /**
     * @param PartInterface$part
     */
    public function preRun(PartInterface $part)
	{
        /**
         * @var $part \Model\Generator\Part\Entity
         */

        /**
         * @var $file \Model\Code\Generator\FileGenerator
         */
        $file = $part->getFile();

        /**
         * @var $table \Model\Cluster\Schema\Table
         */
        $table = $part->getTable();
        $tableNameAsCamelCase = $table->getNameAsCamelCase();

        $tags = array(
            array(
                'name'        => 'see',
                'description' => 'parent::setupDefaultEntityType()',
            ),
        );

        $docblock = new \Zend\Code\Generator\DocBlockGenerator('Устанавливаем тип entity по-умолчанию');
        $docblock->setTags($tags);

        $method = new \Zend\Code\Generator\MethodGenerator();
        $method->setName('setupDefaultEntityType');
        $method->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::VISIBILITY_PROTECTED);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
\$this->_defaultEntityType = '\Model\Entity\\{$tableNameAsCamelCase}Entity';
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
	}

	public function postRun(PartInterface $part)
	{ }
}