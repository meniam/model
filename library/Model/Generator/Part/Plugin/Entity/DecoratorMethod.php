<?php

namespace Model\Generator\Part\Plugin\Entity;
use Model\Generator\Part\PartInterface;
use Model\Code\Generator\DocBlockGenerator;

class DecoratorMethod extends AbstractEntity
{
    /**
     * Реестр геттеров,
     * что бы не наплодить несколько функций с одним именем
     *
     * @var array
     */
    protected static $_data = array();

    public function __construct()
    {
        $this->_setName('DecoratorMethod');
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

        if ($file->getClass()->getDocblock()) {
            $docBlock = $file->getClass()->getDocblock();
        } else {
            $docBlock = new DocBlockGenerator('Сущность ' .  $table->getNameAsCamelCase());
            $file->getClass()->setDocblock($docBlock);
        }

        $columnList = $table->getColumn();
        
        /** @var $columnList \Model\Cluster\Schema\Table\Column[]  */
        foreach ($columnList as $column) {
            $decoratorArray = $column->getDecorator();

            foreach ($decoratorArray as $decorator) {
                $methodName = 'get' . $column->getNameAsCamelCase() . 'As' . $decorator['name'] . 'Decorator';

                if (class_exists('\\Model\\Entity\\Decorator\\' . "{$decorator['name']}Decorator")) {
                    $file->addUse('\\Model\\Entity\\Decorator\\' . "{$decorator['name']}Decorator");
                    $docBlock->setTag(array(
                        'name' => 'method',
                        'description' => "{$decorator['name']}Decorator {$methodName}() {$methodName}() Декорируем данные как {$decorator['name']}"
                    ));
                }
            }
        }
    }
}