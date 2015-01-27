<?php

namespace Model\Generator\Part\Plugin\Entity;
use Model\Generator\Part\PartInterface;
use Model\Cluster\Schema\Table\Link\AbstractLink;
use Model\Generator\Part\Plugin\Entity\Getter;

class RelatedGetter extends AbstractEntity
{
    protected $_data = array();

	public function __construct()
	{
 		$this->_setName('RelatedGetter');
	}

    /**
     * @param PartInterface $part
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

        $linkList = $table->getLink();

        foreach ($linkList as $link) {
            $foreignEntity = $link->getForeignEntity();
            $columnName    = $link->getForeignColumn()->getName();
            $columnComment = $link->getForeignColumn()->getComment();

            switch ($link->getLinkType()) {
                case AbstractLink::LINK_TYPE_ONE_TO_MANY:
                case AbstractLink::LINK_TYPE_MANY_TO_MANY:

                    if ($columnComment) {
                        $shortDescr = "Получить список " . mb_strtolower($columnComment, 'UTF-8') . ' (' . $table->getName() . '.' . $columnName . ')';
                    } else {
                        $shortDescr = 'Получить список ' . $link->getForeignEntity();
                    }

                    $docblock = new \Zend\Code\Generator\DocBlockGenerator($shortDescr);
                    $docblock->setTags(array(
                        array(
                            'name'        => 'return',
                            'description' => '\\Model\\Collection\\' . $link->getForeignColumn()->getTable()->getNameAsCamelCase() . 'Collection',
                        ),
                    ));

                    $method = new \Zend\Code\Generator\MethodGenerator();
                    $method->setName('get' . $link->getForeignEntityAsCamelCase() . 'Collection');
                    $method->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::VISIBILITY_PUBLIC);
                    $method->setDocBlock($docblock);
                    $method->setBody(<<<EOS
return \$this->get('_{$foreignEntity}_collection');
EOS
                    );

                    try {
                        $part->getFile()->getClass()->addMethodFromGenerator($method);
                    } catch (\Exception $e) {

                    }
                case AbstractLink::LINK_TYPE_ONE_TO_ONE:
                case AbstractLink::LINK_TYPE_MANY_TO_ONE:
                    if ($columnComment) {
                        $shortDescr = "Получить связанную сущность" . mb_strtolower($columnComment, 'UTF-8') . ' (' . $table->getName() . '.' . $columnName . ')';
                    } else {
                        $shortDescr = 'Получить связанную сущность ' . $link->getForeignEntity();
                    }

                    $docblock = new \Zend\Code\Generator\DocBlockGenerator($shortDescr);
                    $docblock->setTags(array(
                        array(
                            'name'        => 'return',
                            'description' => '\\Model\\Entity\\' . $link->getForeignColumn()->getTable()->getNameAsCamelCase() . 'Entity',
                        ),
                    ));

                    $method = new \Zend\Code\Generator\MethodGenerator();
                    $method->setName('get' . $link->getForeignEntityAsCamelCase());
                    $method->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::VISIBILITY_PUBLIC);
                    $method->setDocBlock($docblock);
                    $method->setBody(<<<EOS
return \$this->get('_{$foreignEntity}');
EOS
                    );
                try {
                    $part->getFile()->getClass()->addMethodFromGenerator($method);
                } catch (\Exception $e) {

                }
            }
        }
	}

}