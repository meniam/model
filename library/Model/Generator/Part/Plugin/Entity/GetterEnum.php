<?php

namespace Model\Generator\Part\Plugin\Entity;
use Model\Generator\Part\PartInterface;
use Model\Cluster\Schema\Table\Column;

class GetterEnum extends AbstractEntity
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
        $this->_setName('GetterEnum');
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
            if ($column->getColumnType() != 'enum' || substr($column->getName(), 0, 3) == 'is_' || count($column->getEnumValuesAsArray()) < 1) {
                continue;
            }

            $columnName    = $column->getName();

            foreach ($column->getEnumValuesAsArray() as $enumValue) {
                $shortDescr = 'Проверить на соответствие ' . $column->getTable()->getName() . '.' . $columnName . ' значению ' . $enumValue;

                $docblock = new \Zend\Code\Generator\DocBlockGenerator($shortDescr);
                $docblock->setTags(array(
                    array(
                        'name'        => 'return',
                        'description' => 'bool',
                    ),

                ));

                $method = new \Zend\Code\Generator\MethodGenerator();
                $method->setName('is' . $column->getNameAsCamelCase() . implode('', array_map('ucfirst', explode('_', $enumValue))));
                $method->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::VISIBILITY_PUBLIC);
                $method->setDocBlock($docblock);

                $method->setBody(<<<EOS
return \$this->get('{$columnName}') == '{$enumValue}';
EOS
                );

                $part->getFile()->getClass()->addMethodFromGenerator($method);
            }
        }

        foreach ($columnList as $column) {
            if ($column->getColumnType() != 'enum' || substr($column->getName(), 0, 3) != 'is_' || count($column->getEnumValuesAsArray()) != 2) {
                continue;
            }

            $inc = 0;
            foreach ($column->getEnumValuesAsArray() as $enumValue) {
                if (in_array($enumValue, array('y', 'n')) ) {
                    $inc++;
                }
            }

            if ($inc != 2) {
                continue;
            }

            $columnName    = $column->getName();
            $shortDescr = 'Проверить флаг ' . $column->getTable()->getName() . '.' . $columnName;
            $docblock = new \Zend\Code\Generator\DocBlockGenerator($shortDescr);
            $docblock->setTags(array(
                array(
                    'name'        => 'return',
                    'description' => 'bool',
                ),

            ));

            $method = new \Zend\Code\Generator\MethodGenerator();
            $method->setName('is' . substr($column->getNameAsCamelCase(), 2));
            $method->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::VISIBILITY_PUBLIC);
            $method->setDocBlock($docblock);

            $method->setBody(<<<EOS
return \$this->get('{$columnName}') == 'y';
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