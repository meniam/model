<?php

namespace Model\Generator\Part\Plugin\Model;

use Model\Generator\Part\PartInterface;
use Model\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\AbstractMemberGenerator;
use Model\Cluster\Schema\Table\Column;

class InitFilterRules extends AbstractModel
{
    public function __construct()
    {
        $this->_setName('InitFilterRules');
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

        $file->addUse('Model\\Filter\\Filter');

        $columnCollection = $part->getTable()->getColumn();

        $template = '';
        /** @var $column Column */
        foreach ($columnCollection as $column) {
            $name = $column->getName();


            if ($columnConfig = $part->getColumntConfig($column)) {
                if ($columnConfig && isset($columnConfig['filters'])) {
                    foreach ($columnConfig['filters'] as $filter) {
                        $filterParams = isset($validator['params']) ? $this->varExportMin($validator['params'], true) : null;

                        if ($filterParams && $filterParams != 'NULL') {
                            $template .= "\$this->addFilterRule('$name', Filter::getFilterInstance('{$filter['name']}', {$filterParams}));\n";
                        } else {
                            $template .= "\$this->addFilterRule('$name', Filter::getFilterInstance('{$filter['name']}'));\n";
                        }
                    }
                }
            }

/*
            $filterArray = $column->getFilter();

            foreach ($filterArray as $filter) {
                if (empty($filter['params'])) {
                    $template .= "\$this->addFilterRule('$name', Filter::getFilterInstance('{$filter['name']}'));\n";
                } else {
                    $filterParams = $this->varExportMin($filter['params'], true);
                    $template .= "\$this->addFilterRule('$name', Filter::getFilterInstance('{$filter['name']}', {$filterParams}));\n";
                }
            }
*/
            if ($column->isNullable()) {
                $template .= "\$this->addFilterRule('$name', Filter::getFilterInstance('\\Model\\Filter\\Null'));\n";
            }
        }

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
        $method->setName('initFilterRules');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setStatic(false);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
if (\$this->isFilterRules()) {
    return \$this->getFilterRules();
}

{$template}
\$this->setupFilterRules();
return \$this->getFilterRules();
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }

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
            return var_export($var, $return);
        }
    }
}