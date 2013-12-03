<?php

namespace Model\Generator\Part\Plugin\Model;

use Model\Generator\Part\PartInterface;
use Model\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\AbstractMemberGenerator;

use Model\Cluster\Schema\Table\Link\AbstractLink;
use Model\Cluster\Schema\Table\Column;
use \Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\PropertyValueGenerator;

/**
 * Плагин для генерации свойства relation
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class InitDefaults extends AbstractModel
{
    public function __construct()
    {
        $this->_setName('InitDefaults');
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
         * @var $file \Zend\Code\Generator\FileGenerator
         */
        $file = $part->getFile();
        $table = $part->getTable();

        $tags = array(
            array(
                'name'        => 'var',
                'description'        => 'array значения по-умолчанию для полей',
            ),
        );

        $docblock = new \Zend\Code\Generator\DocBlockGenerator('Значения по-умолчанию для полей');
        $docblock->setTags($tags);

        $columnCollection = $table->getColumn();

        if (!$columnCollection) {
            return;
        }

        $defaults = '';

        /** @var $column Column */
        foreach ($columnCollection as $column) {
            $columnName = $column->getName();
            $defaultValue = $column->getColumnDefault();

            if ($defaultValue == 'CURRENT_TIMESTAMP') {
                $defaults .= '$this->setDefaultRule(\'$columnName\', date(\'Y-m-d H:i:s\'));' . "\n";
            } elseif (!empty($defaultValue)) {
                $defaults .= '$this->setDefaultRule(\'' . $columnName . '\', \'' . (string)$defaultValue . '\');' . "\n";
            }
        }

        $tags = array(
            array(
                'name'        => 'return',
                'description' => 'void'
            ),
        );

        $docblock = new DocBlockGenerator('Инициализация значений по-умолчанию');
        $docblock->setTags($tags);

        $method = new MethodGenerator();
        $method->setName('initDefaultsRules');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setFinal(true);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
if (!\$this->isDefaultRules()) {
    {$defaults}
    \$this->setupDefaultsRules();
}
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }
}
