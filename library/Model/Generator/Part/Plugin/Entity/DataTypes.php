<?php

namespace Model\Generator\Part\Plugin\Entity;

use Model\Generator\Part\PartInterface;
use Model\Cluster\Schema\Table\Link\AbstractLink;

class DataTypes extends AbstractEntity
{
	public function __construct()
	{
 		$this->_setName('DataTypes');
	}

    /**
     * @param \Model\Generator\Part\PartInterface $part
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

        $maxColNameLen = $table->getMaxColumnNameLength();

        $columnList = $table->getColumn();

        $docblock = new \Zend\Code\Generator\DocBlockGenerator('Настраиваем типы данных');

        $method = new \Zend\Code\Generator\MethodGenerator();
        $method->setName('setupDataTypes');
        $method->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::VISIBILITY_PROTECTED);
        $method->setDocBlock($docblock);

        $dataTypesText = '';
        foreach ($columnList as $column) {
            $columnName = str_pad('\'' . $column->getName() . '\'', $maxColNameLen + 2, ' ', STR_PAD_RIGHT);
            $columnType = $column->getTypeAsDataTypeConstant();
            $dataTypesText .= "    {$columnName} => {$columnType},\n";
        }

        $linkList = $table->getLink();

        foreach ($linkList as $link) {
            $foreignName    = $link->getForeignTable()->getName();

            $columnComment = $link->getForeignColumn()->getComment();

            switch ($link->getLinkType()) {
                case AbstractLink::LINK_TYPE_ONE_TO_MANY:
                case AbstractLink::LINK_TYPE_MANY_TO_MANY:
                    $columnName = str_pad('\'_' . $foreignName . '_collection' . '\'', $maxColNameLen + 2, ' ', STR_PAD_RIGHT);
                    $dataTypesText .= "    {$columnName} => 'Model\\\\Collection\\\\" . $link->getForeignColumn()->getTable()->getNameAsCamelCase() . "Collection',\n";
                case AbstractLink::LINK_TYPE_ONE_TO_ONE:
                case AbstractLink::LINK_TYPE_MANY_TO_ONE:
                    $columnName = str_pad('\'_' . $foreignName . '\'', $maxColNameLen + 2, ' ', STR_PAD_RIGHT);

                    $dataTypesText .= "    {$columnName} => 'Model\\\\Entity\\\\" . $link->getForeignColumn()->getTable()->getNameAsCamelCase() . "Entity',\n";
            }
        }

        $dataTypesText = rtrim($dataTypesText, "\r\n ,");

        $method->setBody(<<<EOS
\$this->dataTypes = array(\n{$dataTypesText});
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
	}

	public function postRun(PartInterface $part)
	{ }
}