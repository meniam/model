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
         * @var $file \Zend\Code\Generator\FileGenerator
         */
        $file = $part->getFile();

        $file->setUse('App\\Filter\\Filter');

        $columnCollection = $part->getTable()->getColumn();

        $templates = array();
        /** @var $column Column */
        foreach ($columnCollection as $column) {
            $name = $column->getName();
            $template = "    '{$name}' => array(\n";

            $filterArray = $column->getFilter();

            foreach ($filterArray as $filter) {
                if (empty($filter['params'])) {
                    $template .= "          Filter::getFilterInstance('{$filter['name']}'),\n";
                } else {
                    $filterParams = $this->varExportMin($filter['params'], true);
                    $template .= "          Filter::getFilterInstance('{$filter['name']}', {$filterParams}),\n";
                }
            }

            if ($column->isNullable()) {
                $template .= "        Filter::getFilterInstance('\\Zend\\Filter\\Null'),\n";
            }

            $template = rtrim($template, "\r\n, ") . "\n    ),";
            $templates[] = $template;
        }

        $template = rtrim(implode("\n", $templates), "\r\n, ");
        $tableNameAsCamelCase = $part->getTable()->getNameAsCamelCase();

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
if (\$this->filterRules) {
    return \$this->filterRules;
}

\$this->filterRules = array(
{$template}
);

\$this->setupFilterRules();

return \$this->filterRules;
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