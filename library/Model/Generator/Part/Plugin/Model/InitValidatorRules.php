<?php

namespace Model\Generator\Part\Plugin\Model;

use Model\Generator\Part\PartInterface;
use Model\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\AbstractMemberGenerator;
use Model\Cluster\Schema\Table\Column;

class InitValidatorRules extends AbstractModel
{
    public function __construct()
    {
        $this->_setName('initValidatorRules');
    }

    public function preRun(PartInterface $part)
    {
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
            if ($var instanceof \Zend\Code\Generator\ValueGenerator) {
                return $var;
            } else {
                return var_export($var, $return);
            }
        }
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

        $file->setUse('App\\Validator\\Validator');

        $columnCollection = $part->getTable()->getColumn();

        $template = '';
        /** @var $column Column */
        foreach ($columnCollection as $column) {
            $name = $column->getName();
            //$template .= "    '{$name}' => array(\n";

            $requiredFlag = !($column->isNullable() || $column->getName() == 'id');

            if ($column->isNullable() || $column->getName() == 'id') {
                $required = "        'required' => false,\n";
            } else {
                $required = "        'required' => \$required,\n";
            }

            //$template .= "        'name' => '{$name}',\n" . $required;
            //$template .= "        'validators' => array(\n";

            $validatorArray = $column->getValidator();

            foreach ($validatorArray as $validator) {
                $validatorParams = $this->varExportMin($validator['params'], true);
                if ($validatorParams && $validatorParams != 'NULL') {

                    $template .= "\$this->addValidatorRule('{$name}', Validator::getValidatorInstance('{$validator['name']}', {$validatorParams}), " . ($requiredFlag ? 'true' : 'false') . ");\n";
                } else {
                    $template .= "\$this->addValidatorRule('{$name}', Validator::getValidatorInstance('{$validator['name']}'), " . ($requiredFlag ? 'true' : 'false') . ");\n";
                }
            }
      }

        $template = rtrim($template, "\r\n, ");
        //$tableNameAsCamelCase = $part->getTable()->getNameAsCamelCase();

        $tags = array(
            array(
                'name'        => 'param',
                'description' => 'bool $required учитывать поля NOT NULL',
            ),
            array(
                'name'        => 'return',
                'description' => 'array Model массив с фильтрами по полям',
            ),
        );

        $docblock = new DocBlockGenerator('Получить правила для фильтрации ');
        $docblock->setTags($tags);

        $p = new \Zend\Code\Generator\ParameterGenerator('required');
        $p->setDefaultValue(false);


        $method = new MethodGenerator();
        $method->setName('initValidatorRules');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setStatic(false);
        $method->setDocBlock($docblock);
        $method->setParameter($p);

        $method->setBody(<<<EOS
{$template}
\$this->setupValidatorRules(\$required);
return \$required ? \$this->getValidatorOnAdd() : \$this->getValidatorOnUpdate();
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }


}