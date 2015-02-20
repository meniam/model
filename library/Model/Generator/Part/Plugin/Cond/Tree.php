<?php

namespace Model\Generator\Part\Plugin\Cond;
use Model\Cluster\Schema\Table;
use Model\Generator\Part\PartInterface;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\PropertyGenerator;

class Tree extends AbstractCond
{
    public function __construct()
    {
        $this->_setName('Tree');
    }

    /**
     * @param \Model\Generator\Part\Model|\Model\Generator\Part\PartInterface $part
     */
    public function preRun(PartInterface $part)
    {
        /**
         * @var $file \Model\Code\Generator\FileGenerator
         */
        $file = $part->getFile();

        /** @var Table $table */
        $table = $part->getTable();

        /** @var string $tableName */
        //$tableName = $table->getName();

        /** @var $linkList|Link[] \Model\Cluster\Schema\Table\Column */
        //$linkList = $table->getLink();

        if ($table->isTree()) {
            $names = array(
                'WITH_CHILD', 'WITH_CHILD_COLLECTION', 'WITH_ALL_CHILD', 'WITH_ALL_CHILD_COLLECTION'
            );

            foreach ($names as $name) {
                $property = new PropertyGenerator($name, strtolower($name), PropertyGenerator::FLAG_CONSTANT);

                $tags = array(
                    array(
                        'name'        => 'const'
                    ),
                    array(
                        'name'        => 'var',
                        'description' => 'string',
                    ),
                );

                $docblock = new DocBlockGenerator('WITH entity for child list');
                $docblock->setTags($tags);

                $property->setDocBlock($docblock);

                $file->getClass()->addPropertyFromGenerator($property);
            }
        }
    }
}