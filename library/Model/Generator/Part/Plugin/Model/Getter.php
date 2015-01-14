<?php

namespace Model\Generator\Part\Plugin\Model;

use Model\Exception\ErrorException;
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
         * @var $file \Model\Code\Generator\FileGenerator
         */
        $file = $part->getFile();

        $table = $part->getTable();
        $tableName = $table->getName();
        $tableNameAsCamelCase = $table->getNameAsCamelCase();
        $file->addUse('Model\\Cond\\' . $tableNameAsCamelCase . 'Cond');

        $indexList = $part->getTable()->getIndex();
        /*$methods = $this->generateMethodsByLink($part);

        foreach ($methods as $method) {
            $file->getClass()->addMethodFromGenerator($method);
        }*/

        $this->generateMethodsByRelated($part);
        $this->generateMethodsByIndex($part);
        $this->generateDocBlock($part);

        return $file;
    }

    public function generateDocBlock($part)
    {
        /** @var $part \Model\Generator\Part\Model */
        /** @var $file \Model\Code\Generator\FileGenerator */
        $file = $part->getFile();

        $table = $part->getTable();
        $tableName = $table->getName();
        $indexList = $table->getIndex();

        $dbRegistry = array();
        foreach ($indexList as $index) {
            $params = $getBy = array();
            $prepare = '';
            $paramNames = array();
            $checkForEmpty = '';
            $indexColumn = 'id';
            $where = '';

            foreach ($index as $column) {
                $indexColumn = 'id';
                if ($index->getName() == 'PRIMARY') {
                    $indexColumn = $column->getName();
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

                $params[] = new \Zend\Code\Generator\ParameterGenerator($indexColumnName);
                $paramNames[] = '$' . $indexColumnName;
                $paramNamesStr = implode(', ', $paramNames);
                $getBy[]  = $indexColumnNameAsCamelCase;

                $methodDocBlockName = 'get';
                if (!isset($dbRegistry[$methodDocBlockName]) && !$file->getClass()->hasMethod($methodDocBlockName)) {
                    $methodReturnTypePrefix = $table->getNameAsCamelCase();
                    $file->getClass()->getDocBlock()->setTag(array(
                        'name' => 'method',
                        'description' => "{$methodReturnTypePrefix}Entity|mixed {$methodDocBlockName}() {$methodDocBlockName}(Cond \$cond = null) get"
                    ));
                    $dbRegistry[$methodDocBlockName]=1;
                }

                $methodDocBlockName = 'getCollection';
                if (!isset($dbRegistry[$methodDocBlockName]) && !$file->getClass()->hasMethod($methodDocBlockName)) {
                    $methodReturnTypePrefix = $table->getNameAsCamelCase();
                    $file->getClass()->getDocBlock()->setTag(array(
                        'name' => 'method',
                        'description' => "{$methodReturnTypePrefix}Collection|{$methodReturnTypePrefix}Entity[] {$methodDocBlockName}() {$methodDocBlockName}(\$id, Cond \$cond = null) get collection"
                    ));
                    $dbRegistry[$methodDocBlockName]=1;
                }

                $methodDocBlockName = 'getById';
                if (!isset($dbRegistry[$methodDocBlockName]) && !$file->getClass()->hasMethod($methodDocBlockName)) {
                    $file->getClass()->getDocBlock()->setTag(array(
                        'name' => 'method',
                        'description' => $table->getNameAsCamelCase() ."Entity {$methodDocBlockName}() {$methodDocBlockName}(\$id, Cond \$cond = null) get entity by id"
                    ));
                    $dbRegistry[$methodDocBlockName]=1;
                }

                $methodDocBlockName = 'getCollectionById';
                if (!isset($dbRegistry[$methodDocBlockName]) && !$file->getClass()->hasMethod($methodDocBlockName)) {
                    $methodReturnTypePrefix = $table->getNameAsCamelCase();
                    $file->getClass()->getDocBlock()->setTag(array(
                        'name' => 'method',
                        'description' => "{$methodReturnTypePrefix}Collection|{$methodReturnTypePrefix}Entity[] {$methodDocBlockName}() {$methodDocBlockName}(\$id, Cond \$cond = null) get collection by id"
                    ));
                    $dbRegistry[$methodDocBlockName]=1;
                }

                $methodDocBlockName = 'getBy' . implode('And', $getBy);
                if (!isset($dbRegistry[$methodDocBlockName]) && !$file->getClass()->hasMethod($methodDocBlockName)) {
                    $file->getClass()->getDocBlock()->setTag(array(
                        'name' => 'method',
                        'description' => $table->getNameAsCamelCase() . "Entity {$methodDocBlockName}() {$methodDocBlockName}({$paramNamesStr}, Cond \$cond = null) get item"
                    ));
                    $dbRegistry[$methodDocBlockName]=1;
                }

                $methodDocBlockName = 'getCollectionBy' . implode('And', $getBy);
                if (!isset($dbRegistry[$methodDocBlockName]) && !$file->getClass()->hasMethod($methodDocBlockName)) {
                    $methodReturnTypePrefix = $table->getNameAsCamelCase();
                    $file->getClass()->getDocBlock()->setTag(array(
                        'name' => 'method',
                        'description' => "{$methodReturnTypePrefix}Collection|{$methodReturnTypePrefix}Entity[] {$methodDocBlockName}() {$methodDocBlockName}({$paramNamesStr}, Cond \$cond = null) get collection"
                    ));
                    $dbRegistry[$methodDocBlockName]=1;
                }

                $methodDocBlockName = 'getPairBy' . implode('And', $getBy);
                if (!isset($dbRegistry[$methodDocBlockName]) && !$file->getClass()->hasMethod($methodDocBlockName)) {
                    $file->getClass()->getDocBlock()->setTag(array(
                        'name' => 'method',
                        'description' => "array {$methodDocBlockName}() {$methodDocBlockName}({$paramNamesStr}, Cond \$cond = null) get pairs"
                    ));
                    $dbRegistry[$methodDocBlockName]=1;
                }

                $methodDocBlockName = 'getCountBy' . implode('And', $getBy);
                if (!isset($dbRegistry[$methodDocBlockName]) && !$file->getClass()->hasMethod($methodDocBlockName)) {
                    $file->getClass()->getDocBlock()->setTag(array(
                        'name' => 'method',
                        'description' => "int {$methodDocBlockName}() {$methodDocBlockName}({$paramNamesStr}, Cond \$cond = null) get count"
                    ));
                    $dbRegistry[$methodDocBlockName]=1;
                }

                $methodDocBlockName = 'existsBy' . implode('And', $getBy);
                if (!isset($dbRegistry[$methodDocBlockName]) && !$file->getClass()->hasMethod($methodDocBlockName)) {
                    $file->getClass()->getDocBlock()->setTag(array(
                        'name' => 'method',
                        'description' => "int|array {$methodDocBlockName}() {$methodDocBlockName}({$paramNamesStr}, Cond \$cond = null) check for exists"
                    ));
                    $dbRegistry[$methodDocBlockName]=1;
                }
            }
        }

    }

    public function generateMethodsByIndex($part)
    {
        /** @var $part \Model\Generator\Part\Model */
        /** @var $file \Model\Code\Generator\FileGenerator */
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

                //echo $index->getName() . "\n";

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
        /** @var $file \Model\Code\Generator\FileGenerator */
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
                    'description' => "{$foreignTableNameAsCamelCase}Entity|\\Model\\Collection\\{$foreignTableNameAsCamelCase}Collection|int|string|array $" . $foreignEntityNameAsVar,
                ),
                array(
                    'name'        => 'param',
                    'description' =>  $localTableNameAsCamelCase . 'Cond' . ' $cond Дядя Кондиций :-)',
                ),
                array(
                    'name'        => 'return',
                    'description' => $localTableNameAsCamelCase . 'Entity',

                )
            );

            $file->addUse('\\Model\\Entity\\' . $foreignTableNameAsCamelCase . 'Entity');
            $file->addUse('\\Model\\Cond\\' . $foreignTableNameAsCamelCase . 'Cond');
            $file->addUse('\\Model\\Collection\\' . $foreignTableNameAsCamelCase . 'Collection');

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
            $method->setParameter(new \Zend\Code\Generator\ParameterGenerator('cond', $localTableNameAsCamelCase . 'Cond', $nullValue));

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

\${$foreignTableNameAsVar}CollectionCond = {$foreignTableNameAsCamelCase}Cond::init()->columns(array('id', '$foreignColumnName'));
/** @var {$foreignTableNameAsCamelCase}Collection|{$foreignTableNameAsCamelCase}Entity[] \${$foreignTableNameAsCamelCase}Collection */
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
                    $file->addUse('\\Model\\Cond\\' .$foreignTableNameAsCamelCase . 'Cond');

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
                throw new ErrorException('Unknown getter');
            }

            try {
                $part->getFile()->getClass()->addMethodFromGenerator($method);
            } catch (\Exception $e) {}
        }

    }

    public function generateMethodsByLink($part)
    {
        /** @var $part \Model\Generator\Part\Model */
        /** @var $file \Model\Code\Generator\FileGenerator */
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
            $type = "{$columnAsCamelCase}Entity|{$columnAsCamelCase}Collection|array|string|integer";


            $file->addUse('\\Model\\Entity\\' . $columnAsCamelCase . 'Entity');
            $file->addUse('\\Model\\Collection\\' . $columnAsCamelCase . 'Collection');

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