<?php

namespace Model\Generator\Part\Plugin\Model;

use Model\Generator\Part\PartInterface;
use Model\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\PropertyGenerator;

/**
 * Плагин для генерации константных значений модели
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Mihail Rybalka <ruspanzer@gmail.com>
 * @version    SVN: $Id$
 */
class ConstantList extends AbstractModel
{
    public function __construct()
    {
        $this->_setName('ConstantList');
    }

    public function preRun(PartInterface $part)
    {
    }

    public function postRun(PartInterface $part)
    {
        /** @var $part \Model\Generator\Part\Model */
        $file = $part->getFile();

        $this->generateEnumConstantList($part);

        return $file;
    }

    /**
     * @param \Model\Generator\Part\Model $part
     */
    public function generateEnumConstantList($part)
    {
        $file = $part->getFile();
        
        $table = $part->getTable();

        foreach ($table->getColumn() as $column) {
            if ($column->getColumnType() == 'enum') {

                $enumList = $column->getEnumValuesAsArray();

                // пропускаем флаги
                if (substr($column->getName(), 0, 3) == 'is_' && $enumList == array('y', 'n')) {
                    continue;
                }

                foreach ($enumList as $enumValue) {
                    $columnName = $column->getName();
                    $name = strtoupper($columnName . '_' . $enumValue);

                    $property = new PropertyGenerator($name, $enumValue, PropertyGenerator::FLAG_CONSTANT);
                    $tags = array(
                        array(
                            'name'        => 'const'
                        ),
                        array(
                            'name'        => 'var',
                            'description' => 'string',
                        ),
                    );

                    $docblock = new DocBlockGenerator("Значение {$enumValue} поля {$columnName}");
                    $docblock->setTags($tags);

                    $property->setDocBlock($docblock);

                    $file->getClass()->addPropertyFromGenerator($property);
                }
            }
        }
    }

}