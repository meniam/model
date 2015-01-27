<?php

namespace Model\Generator\Part\Plugin\Cond;
use Model\Cluster\Schema\Table;
use Model\Code\Generator\DocBlockGenerator;
use Model\Generator\Part\PartInterface;
use Model\Schema\Table\Link\AbstractLink;
use Model\Code\Generator\FileGenerator;
use Zend\Code\Generator\PropertyGenerator;

class JoinConst extends AbstractCond
{
    /**
     * Состоит из
     * 'name' => array('name' => ''
     *                 'defaultValue' => ''
     *                 'type' => \Zend\Code\Generator\PropertyGenerator::*)
     *
     * @var array|PropertyGenerator[]
     */
    protected $_data = array();

	public function __construct()
	{
 		$this->_setName('JoinConst');
	}

    /**
     * @param \Model\Generator\Part\Model|\Model\Generator\Part\PartInterface $part
     */
    public function preRun(PartInterface $part)
	{
        /**
         * @var Table $table
         */
        $table = $part->getTable();

        /**
         * @var $file \Model\Code\Generator\FileGenerator
         */
        $file = $part->getFile();

        /** @var array|AbstractLink[] $linkList */
        $linkList = $table->getLink();

        foreach ($linkList as $link) {
            $_name = strtoupper($link->getForeignEntity());
            $name = 'JOIN_' . $_name;


            $property = new PropertyGenerator($name, strtolower($_name), PropertyGenerator::FLAG_CONSTANT);

            $tags = array(
                array(
                    'name'        => 'const'
                ),
                array(
                    'name'        => 'var',
                    'description' => 'string',
                ),
            );

            $docblock = new DocBlockGenerator('JOIN сущность ' . $_name);
            $docblock->setTags($tags);

            $property->setDocBlock($docblock);
            $this->_data[$table->getName()][$name] = $property;
        }
	}

    /**
     * @param \Model\Generator\Part\Model|\Model\Generator\Part\PartInterface $part
     */
	public function postRun(PartInterface $part)
	{
        /**
         * @var FileGenerator $file
         */
        $file = $part->getFile();

        /**
         * @var Table $table
         */
        $table = $part->getTable();

        if (isset($this->_data[$table->getName()])) {
            foreach ($this->_data[$table->getName()] as $property) {
                $file->getClass()->addPropertyFromGenerator($property);
            }
        }
    }
}