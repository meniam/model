<?php

namespace Model\Generator\Part\Plugin\Model;

use Model\Code\Generator\DocBlockGenerator;
use Model\Generator\Part\PartInterface;
use Model\Cluster\Schema\Table\Link\AbstractLink;
use Zend\Code\Generator\AbstractMemberGenerator;
use Zend\Code\Generator\MethodGenerator;
use \Zend\Code\Generator\PropertyGenerator;
use \Zend\Code\Generator\PropertyValueGenerator;
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
class Relationdefine extends AbstractModel
{
	public function __construct()
	{
 		$this->_setName('Relationdefine');
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
                'name'        => 'var',
                'description'        => ' array связи',
            ),
        );

        $docblock = new \Zend\Code\Generator\DocBlockGenerator('Связи');
        $docblock->setTags($tags);

        $linkList = $table->getLink();

        /*if ($table->getColumn('parent_id')) {
            echo $table->getName();

            print_r($linkList);
            die;
        }*/

        $relation = array();

        foreach ($linkList as $link) {
            $linkLocalColumnName = $link->getLocalColumn()->getName();

            if ($link->getForeignTable() == $link->getLocalTable() && $link->getLocalColumn() == $link->getForeignColumn()) {
                continue;
            }

            $foreignAliasName = $link->getForeignEntity();

            $rel = $link->toArray();
            unset($rel['name']);

            switch ($link->getLinkType()) {
                case AbstractLink::LINK_TYPE_ONE_TO_ONE:
                    $rel['type'] = new ValueGenerator('AbstractModel::ONE_TO_ONE', ValueGenerator::TYPE_CONSTANT);
                    break;
                case AbstractLink::LINK_TYPE_ONE_TO_MANY:
                    $rel['type'] = new ValueGenerator('AbstractModel::ONE_TO_MANY', ValueGenerator::TYPE_CONSTANT);
                    break;
                case AbstractLink::LINK_TYPE_MANY_TO_ONE:
                    $rel['type'] = new ValueGenerator('AbstractModel::MANY_TO_ONE', ValueGenerator::TYPE_CONSTANT);
                    break;
                case AbstractLink::LINK_TYPE_MANY_TO_MANY:
                    $rel['type'] = new ValueGenerator('AbstractModel::MANY_TO_MANY', ValueGenerator::TYPE_CONSTANT);
                    break;
            }

            if (($link->getLocalColumn()->getName() != 'id' && !$link->getLinkTable())
                || ($link->getLocalColumn()->getName() == 'id' && !$link->getLinkTable() && !$link->getLocalColumn()->isAutoincrement()) )
            {
                $rel['required_link'] = !$link->getLocalColumn()->isNullable();

                $rel['link_method'] = 'link' . $link->getLocalEntityAsCamelCase() . 'To' . $link->getForeignEntityAsCamelCase();
                $rel['unlink_method'] = 'deleteLink' . $link->getLocalEntityAsCamelCase() . 'To' . $link->getForeignEntityAsCamelCase();
            } else {
                if ($table->getName() == 'tag') {
                    if ($link->getLocalEntityAlias() != $link->getLocalTable()->getName()) {
                        $foreignAliasName .= '_as_' . $link->getLocalEntityAlias();
                    }
                }

                $rel['required_link'] = false;
                $rel['link_method'] = 'link' . $link->getLocalEntityAsCamelCase() . 'To' . $link->getForeignEntityAsCamelCase();
                $rel['unlink_method'] = 'deleteLink' . $link->getLocalEntityAsCamelCase() . 'To' . $link->getForeignEntityAsCamelCase();
            }

            $rel['local_entity'] = $link->getLocalEntity();
            $rel['foreign_entity'] = $link->getForeignEntity();

            $relation[$foreignAliasName] = $rel;
            $relation[$foreignAliasName]['foreign_model'] = '\\Model\\' . $link->getForeignTable()->getNameAsCamelCase() . 'Model';
        }

        $property = new PropertyGenerator('relation', $relation, PropertyGenerator::FLAG_PROTECTED);
        //$property->setDocBlock($docblock);

        $method = new MethodGenerator();
        $method->setName('setupRelation');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PROTECTED);

        $body = preg_replace("#^(\\s*)protected #", "\\1", $property->generate()) . "\n";
        $body .= "\$this->setRelation(\$relation);";

        $docblock = new DocBlockGenerator('Настройка связей');
        $method->setDocBlock($docblock);
        $method->setBody($body);

        //$file->getClass()->addPropertyFromGenerator($property);
        $file->getClass()->addMethodFromGenerator($method);

    }
}
