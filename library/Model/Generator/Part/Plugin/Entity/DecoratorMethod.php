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

        $config = $part->getOption('config');
        $configFields =  (isset($config['fields'])) ? $config['fields'] : array();

        $usedMethods = array();

        /** @var $columnList \Model\Cluster\Schema\Table\Column[]  */
        foreach ($columnList as $column) {
            foreach ($configFields as $configField) {
                if (isset($configField['match']) && $columnConfig = $part->getColumntConfig($column)) {
                    /*foreach ($configField['match'] as $match) {
                        $isMatched = false;
                        if (isset($match['type'])) {
                            $matchTypes = is_array($match['type']) ? $match['type'] : array($match['type']);
                            $isMatched = in_array($column->getColumnType(), $matchTypes);
                        }

                        $isMatched = $isMatched && preg_match($match['regexp'], $column->getFullName());

                        $columnLength = $column->getCharacterMaximumLength() ? $column->getCharacterMaximumLength() : $column->getNumericPrecision();

                        if ($isMatched && isset($match['length'])) {
                            foreach ($match['length'] as $operation => $lengthMatch) {
                                $operation = preg_replace('#\s+#', '', $operation);
                                switch ($operation) {
                                    case '<':
                                        $isMatched = ($columnLength < $lengthMatch);
                                        break;
                                    case '>':
                                        $isMatched = ($columnLength > $lengthMatch);
                                        break;
                                    case '>=':
                                        $isMatched = ($columnLength >= $lengthMatch);
                                        break;
                                    case '<=':
                                        $isMatched = ($columnLength <= $lengthMatch);
                                        break;
                                    case '==':
                                        $isMatched = ($columnLength == $lengthMatch);
                                        break;
                                    case '=':
                                        $isMatched = ($columnLength == $lengthMatch);
                                        break;
                                    default:
                                        $isMatched = false;
                                }
                            }
                        }

                        if ($isMatched) {
                            break;
                        }
                    }*/

                    if ($columnConfig && isset($configField['decorators'])) {
                        foreach ($configField['decorators'] as $decorator) {
                            $methodName = 'get' . $column->getNameAsCamelCase() . 'As' . $decorator['name'] . 'Decorator';

                            if (!isset($usedMethods[$methodName])) {
                                $file->addUse('\\Model\\Entity\\Decorator\\' . "{$decorator['name']}Decorator");
                                $docBlock->setTag(array(
                                    'name'        => 'method',
                                    'description' => "{$decorator['name']}Decorator {$methodName}() {$methodName}() Декорируем данные как {$decorator['name']}"
                                ));

                                $usedMethods[$methodName] = 1;
                            }
                        }
                    }
                }
            }

            $decoratorArray = $column->getDecorator();

            foreach ($decoratorArray as $decorator) {
                $methodName = 'get' . $column->getNameAsCamelCase() . 'As' . $decorator['name'] . 'Decorator';

                if (!isset($usedMethods[$methodName])) {
                    $file->addUse('\\Model\\Entity\\Decorator\\' . "{$decorator['name']}Decorator");
                    $docBlock->setTag(array(
                        'name' => 'method',
                        'description' => "{$decorator['name']}Decorator {$methodName}() {$methodName}() Декорируем данные как {$decorator['name']}"
                    ));

                    $usedMethods[$methodName] = 1;
                }
            }
        }
    }
}