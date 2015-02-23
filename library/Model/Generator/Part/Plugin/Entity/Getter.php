<?php

namespace Model\Generator\Part\Plugin\Entity;
use Model\Code\Generator\DocBlockGenerator;
use Model\Generator\Part\PartInterface;
use Model\Cluster\Schema\Table\Column;
use Zend\Code\Generator\AbstractMemberGenerator;
use Zend\Code\Generator\MethodGenerator;

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

        /** @var $columnList Column[]  */
        $columnList = $table->getColumn();

        foreach ($columnList as $column) {
            $columnName    = $column->getName();
            $columnComment = $column->getComment();

            if ($columnComment) {
                $shortDescription = "Get " . mb_strtolower($columnComment, 'UTF-8') . ' (' . $column->getTable()->getName() . '.' . $columnName . ')';
            } else {
                $shortDescription = 'Get ' . $column->getTable()->getName() . '.' . $columnName;
            }

            $docBlock = new DocBlockGenerator($shortDescription);
            $docBlock->setTags(array(
                             array(
                                 'name'        => 'return',
                                 'description' => $column->getTypeAsPhp(),
                             ),

            ));

            $method = new MethodGenerator();
            $method->setName('get' . $column->getNameAsCamelCase());
            $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
            $method->setDocBlock($docBlock);

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