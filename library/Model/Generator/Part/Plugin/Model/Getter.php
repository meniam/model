<?php

namespace Model\Generator\Part\Plugin\Model;

use Model\Generator\Part\PartInterface;
use Model\Cluster\Schema\Table\Link\AbstractLink;
use Model\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\AbstractMemberGenerator;

/**
 * Плагин для генерации методов linkSomething
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Getter extends AbstractModel
{
    public function __construct()
    {
        $this->_setName('Getter');
    }

    public function preRun(PartInterface $part)
    {
    }

    public function postRun(PartInterface $part)
    {
        /**
         * @var $part \Model\Generator\Part\Model
         */

        /**
         * @var $file \Zend\Code\Generator\FileGenerator
         */
        $file = $part->getFile();

        $table = $part->getTable();
        $tableName = $table->getName();
        $tableNameAsCamelCase = $table->getNameAsCamelCase();
        $indexList = $part->getTable()->getIndex();
        /*$methods = $this->generateMethodsByLink($part);

        foreach ($methods as $method) {
            $file->getClass()->addMethodFromGenerator($method);
        }*/
        $file->setUse('Model\\Cond\\' . $tableNameAsCamelCase . 'Cond', 'Cond');

        $this->generateMethodsByRelated($part);
        $this->generateMethodsByIndex($part);

        return $file;
    }

    public function generateMethodsByIndex($part)
    {
        /** @var $part \Model\Generator\Part\Model */
        /** @var $file \Zend\Code\Generator\FileGenerator */
        $file = $part->getFile();

        $table = $part->getTable();
        $tableName = $table->getName();
        $indexList = $table->getIndex();
        foreach ($indexList as $index) {

            $params = $getBy = array();
            $prepare = '';
            $paramNames = array();
            $checkForEmpty = '';
            $indexColumn = 'id';
            $where = '';
            foreach ($index as $column) {
                if ($index->getName() == 'PRIMARY') {
                    $indexColumn = $column->getName();
                }
                if ($index->getName() == 'PRIMARY' || ($index->count() == 1 && $link = $table->getLinkByColumn($column, $table->getName()))) {
                    continue(2);
                }

                $link = $table->getLinkByColumn($column, $table->getName());
                if ($link) {
                    $indexColumnName = $link->getForeignEntity();
                    $indexColumnNameAsVar = $link->getForeignEntityAsVar();
                    $indexColumnNameAsCamelCase = $link->getForeignEntityAsCamelCase();
                } else {
                    $indexColumnName = $column->getName();
                    $indexColumnNameAsVar = $column->getNameAsVar();
                    $indexColumnNameAsCamelCase = $column->getNameAsCamelCase();
                }

                $indexColumnField = $column->getName();

                $params[] = new \Zend\Code\Generator\ParameterGenerator($indexColumnNameAsVar);
                $getBy[]  = $indexColumnNameAsCamelCase;
                $paramNames[] = '$' . $indexColumnNameAsVar;

                if ($link) {
                    $foreignTableNameAsCamelCase = $link->getForeignTable()->getNameAsCamelCase();
                    $prepare .= <<<EOS
\${$indexColumnNameAsVar}Ids = {$foreignTableNameAsCamelCase}Model::getInstance()->getIdsFromMixed(\${$indexColumnNameAsVar});

EOS;

                } else {
                $prepare .= <<<EOS
\${$indexColumnNameAsVar}Ids = \$this->filterValue(\${$indexColumnNameAsVar}, '{$indexColumnName}');

EOS;
                }
                $checkForEmpty .= "empty(\${$indexColumnNameAsVar}Ids) || ";

                $where .= "'`$tableName`.`{$indexColumnField}`' => \${$indexColumnNameAsVar}Ids,\n";
            }

            $paramNamesStr = implode(', ', $paramNames);

            $methodDocBlockName = 'getCollectionBy' . implode('And', $getBy);
            $file->getClass()->getDocBlock()->setTag(array(
                'name' => 'method',
                'description' => '\\Model\\Collection\\' . $table->getNameAsCamelCase() . "Collection {$methodDocBlockName}() {$methodDocBlockName}({$paramNamesStr}, Cond \$cond = null) get collection"
            ));

            $methodDocBlockName = 'getPairBy' . implode('And', $getBy);
            $file->getClass()->getDocBlock()->setTag(array(
                'name' => 'method',
                'description' => "array {$methodDocBlockName}() {$methodDocBlockName}({$paramNamesStr}, Cond \$cond = null) get pairs"
            ));

            $methodDocBlockName = 'getCountBy' . implode('And', $getBy);
            $file->getClass()->getDocBlock()->setTag(array(
                'name' => 'method',
                'description' => "int {$methodDocBlockName}() {$methodDocBlockName}({$paramNamesStr}, Cond \$cond = null) get count"
            ));

            $methodDocBlockName = 'existsBy' . implode('And', $getBy);
            $file->getClass()->getDocBlock()->setTag(array(
                'name' => 'method',
                'description' => "int|array {$methodDocBlockName}() {$methodDocBlockName}({$paramNamesStr}, Cond \$cond = null) check for exists"
            ));

            //@method int borp() borp(int $int1, int $int2) multiply two integers

            $p = new \Zend\Code\Generator\ParameterGenerator('cond');
            $p->setDefaultValue(null);
            $p->setType('Cond');
            $params[] = $p;

            $where = 'array(' . rtrim($where, " \n,") . ")";
            $checkForEmpty = rtrim($checkForEmpty, "\r\n |");
            $method = new \Zend\Code\Generator\MethodGenerator();
            $method->setName('getBy' . implode('And', $getBy));
            $method->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::VISIBILITY_PUBLIC);
            //$method->setDocBlock($docblock);
            $method->setParameters($params);



            $method->setBody(<<<EOS
\$cond = \$this->prepareCond(\$cond);

{$prepare}
if ({$checkForEmpty}) {
    return \$cond->getEmptySelectResult();
}

\$cond->where({$where});

return \$this->get(\$cond);
EOS
            );

            try {
                $part->getFile()->getClass()->addMethodFromGenerator($method);
            } catch (\Exception $e) {}

        }
    }

    public function generateMethodsByRelated($part)
    {
        /** @var $part \Model\Generator\Part\Model */
        /** @var $file \Zend\Code\Generator\FileGenerator */
        $file = $part->getFile();

        $table = $part->getTable();
        $linkList = $table->getLink();

        foreach ($linkList as $link) {
            $localColumnName = $link->getLocalColumn()->getName();
            $localColumnNameAsVar = $link->getLocalColumn()->getNameAsVar();
            $localColumnNameAsCamelCase = $link->getLocalColumn()->getNameAsCamelCase();

            $localEntityName = $link->getLocalEntity();
            $localEntityNameAsCamelCase = $link->getLocalEntityAsCamelCase();
            $localEntityNameAsVar = $link->getLocalEntityAsVar();

            $localTableName = $link->getLocalTable()->getName();
            $localTableNameAsVar = $link->getLocalTable()->getNameAsVar();
            $localTableNameAsCamelCase = $link->getLocalTable()->getNameAsCamelCase();

            $foreignEntityName = $link->getForeignEntity();
            $foreignEntityNameAsCamelCase = $link->getForeignEntityAsCamelCase();
            $foreignEntityNameAsVar = $link->getForeignEntityAsVar();

            $foreignColumnName = $link->getForeignColumn()->getName();
            $foreignColumnNameAsVar = $link->getForeignColumn()->getNameAsVar();
            $foreignColumnNameAsCamelCase = $link->getForeignColumn()->getNameAsCamelCase();

            $foreignTableName = $link->getForeignTable()->getName();
            $foreignTableNameAsVar = $link->getForeignTable()->getNameAsVar();
            $foreignTableNameAsCamelCase = $link->getForeignTable()->getNameAsCamelCase();

            $linkTableName = $link->getLinkTable() ? $link->getLinkTable()->getName() : '';
            $linkTableLocalColumnName = $link->getLinkTableLocalColumn() ? $link->getLinkTableLocalColumn()->getName() : '';
            $linkTableForeignColumnName = $link->getLinkTableForeignColumn() ? $link->getLinkTableForeignColumn()->getName() : '';

            $tags = array(
                array(
                    'name'        => 'param',
                    'description' => "\\Model\\Entity\\{$foreignTableNameAsCamelCase}Entity|\\Model\\Collection\\{$foreignTableNameAsCamelCase}Collection|int|string|array $" . $foreignEntityNameAsVar,
                ),
                array(
                    'name'        => 'param',
                    'description' => '\\Model\Cond\\' . $localTableNameAsCamelCase . 'Cond' . ' $cond Дядя Кондиций :-)',
                ),
                array(
                    'name'        => 'return',
                    'description' => "\\Model\\Entity\\"  . $localTableNameAsCamelCase . 'Entity',

                )
            );

            $docblock = new DocBlockGenerator("Получить запись {$localEntityName} по {$foreignEntityNameAsVar}");
            $docblock->setTags($tags);

            $params = array(new ParameterGenerator($foreignEntityNameAsVar));

            $methodName = 'get';

            if ($localEntityNameAsCamelCase == $localTableNameAsCamelCase) {
                $methodName .= 'By' . $foreignEntityNameAsCamelCase;
            } else {
                $methodName .= $localEntityNameAsCamelCase . 'By' . $foreignEntityNameAsCamelCase;
            }

            $nullValue = new \Zend\Code\Generator\ValueGenerator('null', \Zend\Code\Generator\ValueGenerator::TYPE_CONSTANT);
            $method = new \Zend\Code\Generator\MethodGenerator();
            $method->setName($methodName);
            $method->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::VISIBILITY_PUBLIC);
            $method->setDocBlock($docblock);
            $method->setParameters($params);
            $method->setParameter(new \Zend\Code\Generator\ParameterGenerator('cond', '\\Model\Cond\\' . $localTableNameAsCamelCase . 'Cond', $nullValue));

            // Только прямая связь
            if (($link->getLinkType() == AbstractLink::LINK_TYPE_MANY_TO_ONE ||
                $link->getLinkType() == AbstractLink::LINK_TYPE_ONE_TO_ONE)
                    && !$link->getLinkTable()
                    && $link->isDirect())
            {
                $method->setBody(<<<EOS
\$cond = \$this->prepareCond(\$cond);

\${$foreignTableNameAsVar}Ids = {$foreignTableNameAsCamelCase}Model::getInstance()->getIdsFromMixed(\${$foreignEntityNameAsVar});

if (!\${$foreignTableNameAsVar}Ids) {
    return \$cond->getEmptySelectResult();
}

\$cond->where(array('`{$localTableName}`.`{$localColumnName}`' => \${$foreignTableNameAsVar}Ids));

return \$this->get(\$cond);
EOS
                );
            } elseif (($link->getLinkType() == AbstractLink::LINK_TYPE_ONE_TO_ONE || $link->getLinkType() == AbstractLink::LINK_TYPE_ONE_TO_MANY) && !$link->getLinkTable()) {
                if ($localColumnName == $foreignColumnName && $link->getLinkType() == AbstractLink::LINK_TYPE_ONE_TO_ONE) {
                    $method->setBody(<<<EOS
\$cond = \$this->prepareCond(\$cond);

\${$localEntityNameAsVar}Ids = \$this->getIdsFromMixed(\${$foreignTableNameAsVar});

if (!\${$localEntityNameAsVar}Ids) {
    return \$cond->getEmptySelectResult();
}

\$cond->where(array('`{$localTableName}`.`{$localColumnName}`' => \${$localEntityNameAsVar}Ids));

return \$this->get(\$cond);
EOS
                    );

                } else {
                $method->setBody(<<<EOS
\$cond = \$this->prepareCond(\$cond);

\${$foreignTableNameAsVar}CollectionCond = \\Model\\Cond\\{$foreignTableNameAsCamelCase}Cond::init()->columns(array('id', '$foreignColumnName'));
\${$foreignTableNameAsVar}Collection = {$foreignTableNameAsCamelCase}Model::getInstance()->getCollectionById(\${$foreignEntityNameAsVar}, \${$foreignTableNameAsVar}CollectionCond);

\${$localEntityNameAsVar}Ids = array();
foreach (\${$foreignTableNameAsVar}Collection as \${$foreignTableNameAsVar}) {
    \${$localEntityNameAsVar}Ids[] = \${$foreignTableNameAsVar}->get{$localEntityNameAsCamelCase}Id();
}
\${$localEntityNameAsVar}Ids = \$this->getIdsFromMixed(\${$localEntityNameAsVar}Ids);

if (!\${$localEntityNameAsVar}Ids) {
    return \$cond->getEmptySelectResult();
}

\$cond->where(array('`{$localTableName}`.`{$localColumnName}`' => \${$localEntityNameAsVar}Ids));

return \$this->get(\$cond);
EOS
                );
                }

            } elseif ($link->getLinkTable()) {
                $method->setBody(<<<EOS
\$cond = \$this->prepareCond(\$cond);

\${$foreignTableNameAsVar}Ids = {$foreignTableNameAsCamelCase}Model::getInstance()->getIdsFromMixed(\${$foreignEntityNameAsVar});

if (!\${$foreignTableNameAsVar}Ids) {
    return \$cond->getEmptySelectResult();
}

\$cond->joinRule('join_link_table_{$linkTableName}', Cond::JOIN_INNER, '`{$linkTableName}`', '`$linkTableName`.`$linkTableLocalColumnName` = `{$localTableName}`.`{$localColumnName}`');
\$cond->where(array('`$linkTableName`.`$linkTableForeignColumnName`' => \${$foreignTableNameAsVar}Ids));

return \$this->execute(\$cond);
EOS
                );
            } else {
                throw new \Model\Exception\ErrorException('Unknown getter');
            }

            try {
                $part->getFile()->getClass()->addMethodFromGenerator($method);
            } catch (\Exception $e) {}
        }

    }

    public function generateMethodsByLink($part)
    {
        /** @var $part \Model\Generator\Part\Model */
        /** @var $file \Zend\Code\Generator\FileGenerator */
        $file = $part->getFile();

        $table = $part->getTable();
        $tableNameAsCamelCase = $part->getTable()->getNameAsCamelCase();
        $indexList = $table->getIndex();

/*        $userStat = $part->getTable()->getSchema()->getTable('user');
        $indexList = $userStat->getIndex();
        foreach ($indexList as $index) {
            print_r($index->toArray());
            $column = reset($index);
            if ($index->count() > 1 || !($link = $table->getLinkByColumn($column))) {

            }
        }
        die;*/
        $methods = array();



        foreach ($indexList as $index) {
            if ($index->getName() == 'PRIMARY') {
                continue;
            }

            $column = reset($index);

            if ($index->count() > 1 || !($link = $table->getLinkByColumn($column, $table->getName()))) {
                continue;
            }

            if ($link->getLocalTable() == $table->getName()) {
                $direct = true;
            } else {
                $direct = false;
            }



            $columnAsCamelCase = $link->getForeignTable()->getnameAsCamelCase();
            $columnAsVar = $link->getForeignTable()->getnameAsVar();
            $columnName = $link->getForeignTable()->getName();

            $localColumnName = $link->getLocalColumn()->getName();


            $localTableName = $link->getLocalTable()->getName();
            $type = "\\Model\\Entity\\{$columnAsCamelCase}Entity|\\Model\\Collection\\{$columnAsCamelCase}Collection|array|string|integer";

            $tags[] = array(
                'name'        => 'param',
                'description' => "{$type} $" . $columnAsVar,
            );

            $params[] = new \Zend\Code\Generator\ParameterGenerator($columnAsVar);

            $docblock = new DocBlockGenerator('Получить один элемен по ');
            $docblock->setTags($tags);


            $method = new \Zend\Code\Generator\MethodGenerator();
            $method->setName('getBy' . $columnAsCamelCase);

            $method->setParameters($params);


            $method->setVisibility(\Zend\Code\Generator\AbstractMemberGenerator::VISIBILITY_PUBLIC);
            $method->setStatic(true);
            $method->setDocBlock($docblock);

//            print_r($link->toArray());z

            if (($link->getLinkType() == AbstractLink::LINK_TYPE_ONE_TO_ONE ||
                $link->getLinkType() == AbstractLink::LINK_TYPE_MANY_TO_ONE) && $direct) {
                    $method->setBody(<<<EOS
\$cond = \$this->prepareCond(\$cond);

\${$columnAsVar}Ids = {$columnAsCamelCase}Model::getInstance()->getIdsFromMixed(\${$columnAsVar});

if (!\${$columnAsVar}Ids) {
    return \$cond->getEmptySelectResult();
}

\$cond->where(array('`{$localTableName}`.`{$localColumnName}`' => \${$columnAsVar}Ids));

return \$this->get(\$cond);
EOS
                    );

            } elseif (($link->getLinkType() == AbstractLink::LINK_TYPE_ONE_TO_ONE ||
                    $link->getLinkType() == AbstractLink::LINK_TYPE_MANY_TO_ONE) && !$direct) {

                $localTableName = $link->getLocalTable()->getName();
                $localColumnName = $link->getLocalColumn()->getName();
                $localTableNameAsVar = $link->getLocalTable()->getNameAsVar();
                $localTableNameAsCamelCase = $link->getLocalTable()->getNameAsCamelCase();

                $method->setBody(<<<EOS
\$cond = \$this->prepareCond(\$cond);

\${$columnAsVar}Collection = {$columnAsCamelCase}Model::getInstance()->getCollectionBy{$columnAsCamelCase}(\${$columnAsVar});

\${$localTableNameAsVar}Ids = array();
foreach (\${$columnAsVar}Collection as \${$columnAsVar}) {
    \${$localTableNameAsVar}Ids[] = \${$columnAsVar}->get{$localTableNameAsCamelCase}Id();
}
\${$localTableNameAsVar}Ids = \$this->getIdsFromMixed(\${$localTableNameAsVar}Ids);

if (!\${$localTableNameAsVar}Ids) {
    return \$cond->getEmptySelectResult();
}

\$cond->where(array('`{$localTableName}`.`{$localColumnName}`' => \${$localTableNameAsVar}Ids));

return \$this->get(\$cond);
EOS
                );

            } elseif (($link->getLinkType() == AbstractLink::LINK_TYPE_MANY_TO_MANY)) {
                die('ok');
            }


            $methods[] = $method;
        }

        return $methods;
    }

}