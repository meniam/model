<?php

namespace Model\Generator\Part\Plugin\Model;

use Model\Code\Generator\DocBlockGenerator;
use Model\Generator\Part\PartInterface;
use Model\Cluster\Schema\Table\Index\AbstractIndex;
use Zend\Code\Generator\AbstractMemberGenerator;
use Zend\Code\Generator\MethodGenerator;
use \Zend\Code\Generator\PropertyGenerator;
use \Zend\Code\Generator\ValueGenerator;

/**
 * Плагин для генерации свойства relation
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class IndexList extends AbstractModel
{
    public function __construct()
    {
        $this->_setName('Indexlist');
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
        $table = $part->getTable();

        $tags = array(
            array(
                'name'        => 'return',
                'description' => 'array',
            ),
        );

        $docblock = new DocBlockGenerator('Initialize indexes');
        $docblock->setTags($tags);

        $resultIndexList = array();
        $indexList = $table->getIndex();

        foreach ($indexList as $index) {
            $resIndex = $index->toArray();
            $resIndex['column_list'] = array();
            switch ($index->getType()) {
                case AbstractIndex::TYPE_PRIMARY:
                    $resIndex['type'] = new ValueGenerator('AbstractModel::INDEX_PRIMARY', ValueGenerator::TYPE_CONSTANT);
                    break;
                case AbstractIndex::TYPE_KEY:
                    $resIndex['type'] = new ValueGenerator('AbstractModel::INDEX_KEY', ValueGenerator::TYPE_CONSTANT);
                    break;
                case AbstractIndex::TYPE_UNIQUE:
                    $resIndex['type'] = new ValueGenerator('AbstractModel::INDEX_UNIQUE', ValueGenerator::TYPE_CONSTANT);
                    break;
            }
            foreach ($resIndex['columns'] as $col) {
                $resIndex['column_list'][] = $col['column_name'];
            }

            unset($resIndex['columns']);
            $resultIndexList[$index->getName()] = $resIndex;
        }

        $property = new PropertyGenerator('indexList', $resultIndexList, PropertyGenerator::FLAG_PROTECTED);

        $body = preg_replace("#^(\\s*)protected #", "\\1", $property->generate()) . "\n";

        $method = new MethodGenerator();
        $method->setName('initIndexList');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setFinal(true);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
$body
\$this->indexList = \$indexList;
\$this->setupIndexList();
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }
}
