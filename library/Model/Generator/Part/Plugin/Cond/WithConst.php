<?php

namespace Model\Generator\Part\Plugin\Cond;
use Model\Cluster\Schema\Table;
use Model\Generator\Part\PartInterface;
use Model\Cluster\Schema\Table\Link\AbstractLink;

class WithConst extends AbstractCond
{
    /**
     * Состоит из
     * 'name' => array('name' => ''
     *                 'defaultValue' => ''
     *                 'type' => \Zend\Code\Generator\PropertyGenerator::*)
     *
     * @var array
     */
    protected static $_data = array();

	public function __construct()
	{
 		$this->_setName('WithConst');
	}

    /**
     * @param \Model\Generator\Part\Model|\Model\Generator\Part\PartInterface $part
     */
	public function preRun(PartInterface $part)
	{
        /**
         * @var $file \Zend\Code\Generator\FileGenerator
         */
        $file = $part->getFile();

        /** @var Table $table */
        $table = $part->getTable();

        /** @var string $tableName */
        $tableName = $table->getName();

        /** @var $linkList|Link[] \Model\Cluster\Schema\Table\Column */
        $linkList = $table->getLink();

        foreach ($linkList as $link) {
            $_name = $link->getForeignEntity();

            switch ($link->getLinkType()) {
                case AbstractLink::LINK_TYPE_ONE_TO_ONE:
                case AbstractLink::LINK_TYPE_ONE_TO_MANY:
                case AbstractLink::LINK_TYPE_MANY_TO_ONE:
                case AbstractLink::LINK_TYPE_MANY_TO_MANY:


                    $name = 'WITH_' . strtoupper($_name);

                    $property = new \Zend\Code\Generator\PropertyGenerator($name, strtolower($_name), \Zend\Code\Generator\PropertyGenerator::FLAG_CONSTANT);

                    $tags = array(
                        array(
                            'name'        => 'const'
                        ),
                        array(
                            'name'        => 'var',
                            'description' => 'string',
                        ),
                    );

                    $docblock = new \Zend\Code\Generator\DocBlockGenerator('WITH сущность ' . $link->getForeignColumn()->getTable()->getNameAsCamelCase());
                    $docblock->setTags($tags);

                    $property->setDocBlock($docblock);

                    self::$_data[$tableName][$name] = $property;


                    $name = 'WITH_' . strtoupper($_name) . '_COUNT';

                    $property = new \Zend\Code\Generator\PropertyGenerator($name, strtolower($_name) . '_count', \Zend\Code\Generator\PropertyGenerator::FLAG_CONSTANT);

                    $tags = array(
                        array(
                            'name'        => 'const'
                        ),
                        array(
                            'name'        => 'var',
                            'description' => 'string',
                        ),
                    );

                    $docblock = new \Zend\Code\Generator\DocBlockGenerator('WITH сущность ' . $link->getForeignColumn()->getTable()->getNameAsCamelCase());
                    $docblock->setTags($tags);

                    $property->setDocBlock($docblock);

                    self::$_data[$tableName][$name] = $property;

                    break;
            }

            switch ($link->getLinkType()) {
                case AbstractLink::LINK_TYPE_ONE_TO_MANY:
                case AbstractLink::LINK_TYPE_MANY_TO_MANY:
                    $name = 'WITH_' .strtoupper($_name) . "_COLLECTION";
                    $property = new \Zend\Code\Generator\PropertyGenerator($name, $_name . '_collection', \Zend\Code\Generator\PropertyGenerator::FLAG_CONSTANT);

                    $tags = array(
                        array(
                            'name'        => 'const'
                        ),
                        array(
                            'name'        => 'var',
                            'description' => 'string',
                        ),
                    );

                    $docblock = new \Zend\Code\Generator\DocBlockGenerator('WITH сущность коллекции ' . $link->getForeignColumn()->getTable()->getNameAsCamelCase());
                    $docblock->setTags($tags);

                    $property->setDocBlock($docblock);

                    self::$_data[$tableName][$name] = $property;
                    break;
            }
        }
	}

    /**
     * @param \Model\Generator\Part\Model|\Model\Generator\Part\PartInterface $part
     */
	public function postRun(PartInterface $part)
	{
        /**
         * @var $file \Zend\Code\Generator\FileGenerator
         */
        $file = $part->getFile();

        /** @var Table $table */
        $table = $part->getTable();

        /** @var string $tableName */
        $tableName = $table->getName();

        if (isset(self::$_data[$tableName])) {
            foreach (self::$_data[$tableName] as $property) {
                $file->getClass()->addPropertyFromGenerator($property);
            }
        }
    }
}