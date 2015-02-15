<?php

namespace Model\Generator\Part\Plugin\Model;

use Model\Generator\Part\PartInterface;
use Model\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\AbstractMemberGenerator;
use Model\Cluster\Schema\Table\Column;
use Zend\Code\Generator\ValueGenerator;

class InitValidatorRules extends AbstractModel
{
    public function __construct()
    {
        $this->_setName('initValidatorRules');
    }

    public function preRun(PartInterface $part)
    {
    }

    /**
     * @param      $var
     * @param bool $return
     *
     * @return mixed|string|null
     */
    public function varExportMin($var, $return = false) {
        if (is_array($var)) {
            $toImplode = array();
            foreach ($var as $key => $value) {
                $toImplode[] = var_export($key, true).' => '.$this->varExportMin($value, true);
            }
            $code = 'array('.implode(', ', $toImplode).')';
            if ($return) return $code;
            else echo $code;
        } else {
            if ($var instanceof ValueGenerator) {
                return $var;
            } else {
                return var_export($var, $return);
            }
        }

        return null;
    }

    /**
     * @param        $paramArray
     * @param Column $column
     *
     * @return array
     */
    public function prepareValidatorParams($paramArray, Column $column)
    {
        if (!is_array($paramArray)) {
            return $paramArray;
        }

        foreach ($paramArray as $k => $value) {
            switch ((string)$value) {
                case 'COLUMN_CHAR_LENGTH':
                    $value = $column->getCharacterMaximumLength();
                    break;
                case 'COLUMN_ENUM_VALUES':
                    $value = $column->getEnumValuesAsArray();
                    break;
                case 'MAX_VALUE':
                    $value = $column->getMaxValue();
                    break;
                case 'MIN_VALUE':
                    $value = $column->getMinValue();
                    break;
            }
            $paramArray[$k] = $value;
        }

        return $paramArray;
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

        $file->addUse('Model\\Validator\\Validator');

        $columnCollection = $part->getTable()->getColumn();

        $template = '';
        /** @var $column Column */
        foreach ($columnCollection as $column) {
            $name = $column->getName();
            $requiredFlag = !($column->isNullable() || $column->getName() == 'id');

            if ($columnConfig = $part->getColumntConfig($column)) {
                if ($columnConfig && isset($columnConfig['validators'])) {
                    foreach ($columnConfig['validators'] as $validator) {
                        if (isset($validator['params'])) {
                            $validatorParams = $this->prepareValidatorParams($validator['params'], $column);
                            $validatorParams = $this->varExportMin($validatorParams, true);
                        } else {
                            $validatorParams = null;
                        }

                        if ($validatorParams && $validatorParams != 'NULL') {
                            $template .= "\$this->addValidatorRule('{$name}', Model::getValidatorAdapter()->getValidatorInstance('{$validator['name']}', {$validatorParams}), " . ($requiredFlag ? 'true' : 'false') . ");\n";
                        } else {
                            $template .= "\$this->addValidatorRule('{$name}', Model::getValidatorAdapter()->getValidatorInstance('{$validator['name']}'), " . ($requiredFlag ? 'true' : 'false') . ");\n";
                        }
                    }
                }
            }
        }

        $template = rtrim($template, "\r\n, ");
        //$tableNameAsCamelCase = $part->getTable()->getNameAsCamelCase();

        $tags = array(
            array(
                'name'        => 'return',
                'description' => 'array Model массив с фильтрами по полям',
            ),
        );

        $docblock = new DocBlockGenerator('Получить правила для фильтрации ');
        $docblock->setTags($tags);


        $method = new MethodGenerator();
        $method->setName('initValidatorRules');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setStatic(false);
        $method->setFinal(true);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
{$template}
\$this->setupValidatorRules();
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }


}