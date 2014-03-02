<?php

namespace Model\Generator\Part\Plugin\Entity;
use Model\Generator\Part\PartInterface;
use Model\Cluster\Schema\Table\Column;

class Getter extends AbstractEntity
{
    /**
     * Реестр геттеров,
     * что бы не наплодить несколько функций с одним именем
     *
     * @var array
     */
    protected $_data = array();

	public function __construct()
	{
 		$this->_setName('Getter');
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

//        $tableNameAsCamelCase = $table->getNameAsCamelCase();

        /** @var $columnList Column[]  */
        $columnList = $table->getColumn();

        foreach ($columnList as $column) {
            $columnName    = $column->getName();
            $columnComment = $column->getComment();

            if ($columnComment) {
                $shortDescr = "Получить " . mb_strtolower($columnComment, 'UTF-8') . ' (' . $column->getTable()->getName() . '.' . $columnName . ')';
            } else {
                $shortDescr = 'Получить ' . $column->getTable()->getName() . '.' . $columnName;
            }

            $docblock = new \Zend\Code\Generator\DocBlockGenerator($shortDescr);
            $docblock->setTags(array(
                             array(
                                 'name'        => 'return',
                                 'description' => $column->getTypeAsPhp(),
                             ),

            ));

            $method = new \Zend\Code\Generator\MethodGenerator();
            $method->setName('get' . $column->getNameAsCamelCase());
            $method->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::VISIBILITY_PUBLIC);
            $method->setDocBlock($docblock);

            $method->setBody(<<<EOS
return \$this->get('{$columnName}');
EOS
            );

            $part->getFile()->getClass()->addMethodFromGenerator($method);
        }

	}

    /**
     * @param \Model\Generator\Part\PartInterface $part
     */
    public function postRun(PartInterface $part)
	{
    }

}