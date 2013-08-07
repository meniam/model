<?php
/**
 * Link method generator for Model
 *
 * LICENSE: THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NON INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @category   Cond
 * @package    Model
 * @author     Eugene Myazin <eugene.myazin@gmail.com>
 * @copyright  2008-20013 Eugene Myazin <eugene.myazin@gmail.com>
 * @license    https://github.com/meniam/model/blob/master/MIT-LICENSE.txt  MIT License
 */

namespace Model\Generator\Part\Plugin\Model;

use Model\Cluster\Schema\Table\Link\AbstractLink;
use Model\Code\Generator\DocBlockGenerator;
use Model\Generator\Part\PartInterface;
use Zend\Code\Generator\AbstractMemberGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\ValueGenerator;

/**
 * Плагин для генерации методов linkSomething
 *
 * @category   Generator
 * @package    Model
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Link extends AbstractModel
{
    public function __construct()
    {
        $this->_setName('Link');
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

        $linkList = $part->getTable()->getLink();

        foreach ($linkList as $link) {
            if ($link->getLinkTable()) {
                $method = $this->getLinkMethodUsedLinkTable($link);
                $file->getClass()->addMethodFromGenerator($method);

                $method = $this->getDeleteLinkMethodUsedLinkTable($link);
                $file->getClass()->addMethodFromGenerator($method);
            } else {
                $method = $this->getLinkMethodWithoutLinkTable($link);
                $file->getClass()->addMethodFromGenerator($method);

                $method = $this->getDeleteLinkMethodWithoutLinkTable($link);
                $file->getClass()->addMethodFromGenerator($method);
            }
        }
    }


    /**
     * Генерим метод линковки основанный на таблице связки
     *
     * @param AbstractLink $link
     * @return MethodGenerator
     */
    protected function getLinkMethodUsedLinkTable(AbstractLink $link)
    {
        $localEntity = $link->getLocalEntity();
        $localEntityAsVar = $link->getLocalEntityAsVar();
        $localEntityAsCamelCase = $link->getLocalEntityAsCamelCase();

        $foreignTableAsCamelCase = $link->getForeignTable()->getNameAsCamelCase();
        $foreignEntity = $link->getForeignEntity();
        $foreignEntityAsVar = $link->getForeignEntityAsVar();
        $foreignEntityAsCamelCase = $link->getForeignEntityAsCamelCase();

        $linkTableName = $link->getLinkTable()->getName();

        $linkTableLocalColumn = $link->getLinkTableLocalColumn()->getName();
        $linkTableForeignColumn = $link->getLinkTableForeignColumn()->getName();

        $tags = array(
            array(
                'name'        => 'param',
                'description' => 'mixed $' . $localEntityAsVar
            ),

            array(
                'name'        => 'param',
                'description' => 'mixed $' . $foreignEntityAsVar
            ),

            array(
                'name'        => 'param',
                'description' => "\$isAppend Если false, сначала очищяются по {$localEntityAsVar}, потом добавляются"
            ),

            array(
                'name'        => 'return',
                'description' => 'Result'
            ),
        );

        $docblock = new DocBlockGenerator('Привязать ' . $localEntityAsCamelCase . ' к ' . $foreignEntityAsCamelCase);
        $docblock->setTags($tags);

        $method = new MethodGenerator();
        $method->setName('link' . $localEntityAsCamelCase . 'To' . $foreignEntityAsCamelCase);
        $method->setParameter(new ParameterGenerator($localEntityAsVar, 'mixed'));
        $method->setParameter(new ParameterGenerator($foreignEntityAsVar, 'mixed'));
        $method->setParameter(new ParameterGenerator('isAppend', 'mixed', true));
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setDocBlock($docblock);

        if ($link->isDirect()) {
            $method->setBody(<<<EOS
\${$localEntityAsVar}Ids = array_unique(\$this->getIdsFromMixed(\${$localEntityAsVar}));

\$result = new Result();
\$resultFlag = true;

if (count(\${$localEntityAsVar}Ids) == 0) {
    \$result->setResult(\$resultFlag);
    return \$result;
}

if (!\$isAppend) {
    \$this->deleteLink{$localEntityAsCamelCase}To{$foreignEntityAsCamelCase}(\${$localEntityAsVar}Ids);
}

\${$foreignEntityAsVar}Ids = {$foreignTableAsCamelCase}Model::getInstance()->getIdsFromMixed(\${$foreignEntityAsVar});
\${$foreignEntityAsVar}Ids = array_unique(\${$foreignEntityAsVar}Ids);

if (count(\${$foreignEntityAsVar}Ids) == 0) {
    \$result->setResult(\$resultFlag);
    return \$result;
}

\$sql = "INSERT INTO `{$linkTableName}` SET `{$linkTableLocalColumn}` = :{$linkTableLocalColumn}, `{$linkTableForeignColumn}` = :{$linkTableForeignColumn}";
\$stmt = \$this->getDb()->prepare(\$sql);

\$result = new Result();
\$resultFlag = true;
foreach (\${$localEntityAsVar}Ids as \${$localEntityAsVar}Id) {
    foreach (\${$foreignEntityAsVar}Ids as \${$foreignEntityAsVar}Id) {
        try {
            \$stmt->execute(array('{$linkTableLocalColumn}' => \${$localEntityAsVar}Id, '{$linkTableForeignColumn}' => \${$foreignEntityAsVar}Id));
        } catch (\Exception \$e) {
            // Если операция добавления, то ничего страшного
            // если дубль базы, то тоже - все ок
            if (!\$isAppend || \$e->getCode() != 23000) {
                \$resultFlag = false;
                \$result->addChild('add_link_failed', \$this->getGeneralErrorResult("Add link {$localEntity} to {$foreignEntity} failed"));
            }
       }
    }
}

\$result->setResult(\$resultFlag);

return \$result;
EOS
            );
        } else {
            $method->setBody(<<<EOS

return {$foreignTableAsCamelCase}Model::getInstance()->link{$foreignEntityAsCamelCase}To{$localEntityAsCamelCase}(\${$foreignEntityAsVar}, \${$localEntityAsVar}, \$isAppend);
EOS
            );

        }

        return $method;

    }

    /**
     * @param AbstractLink $link
     * @return MethodGenerator
     */
    protected function getDeleteLinkMethodUsedLinkTable(AbstractLink $link)
    {
        $localEntity = $link->getLocalEntity();
        $localEntityAsVar = $link->getLocalEntityAsVar();
        $localEntityAsCamelCase = $link->getLocalEntityAsCamelCase();

        $foreignTableAsCamelCase = $link->getForeignTable()->getNameAsCamelCase();
        $foreignEntity = $link->getForeignEntity();
        $foreignEntityAsVar = $link->getForeignEntityAsVar();
        $foreignEntityAsCamelCase = $link->getForeignEntityAsCamelCase();

        $linkTableName = $link->getLinkTable()->getName();

        $linkTableLocalColumn = $link->getLinkTableLocalColumn()->getName();
        $linkTableForeignColumn = $link->getLinkTableForeignColumn()->getName();

        $tags = array(
            array(
                'name'        => 'param',
                'description' => 'mixed $' . $localEntityAsVar
            ),

            array(
                'name'        => 'param',
                'description' => 'mixed $' . $foreignEntityAsVar
            ),

            array(
                'name'        => 'return',
                'description' => '\Model\Result\Result'
            ),
        );

        $docblock = new DocBlockGenerator('Отвязать ' . $localEntityAsCamelCase . ' от ' . $foreignEntityAsCamelCase);
        $docblock->setTags($tags);

        $method = new MethodGenerator();
        $method->setName('deleteLink' . $localEntityAsCamelCase . 'To' . $foreignEntityAsCamelCase);
        $method->setParameter(
            new ParameterGenerator($localEntityAsVar,
                'mixed',
                new ValueGenerator('null', ValueGenerator::TYPE_CONSTANT)));

        $method->setParameter(
            new ParameterGenerator($foreignEntityAsVar,
                'mixed',
                new ValueGenerator('null', ValueGenerator::TYPE_CONSTANT)));

        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setDocBlock($docblock);

        if ($link->isDirect()) {

            $method->setBody(<<<EOS
\${$localEntityAsVar}Ids = \$this->getIdsFromMixed(\${$localEntityAsVar});
\${$foreignEntityAsVar}Ids = {$foreignEntityAsCamelCase}Model::getInstance()->getIdsFromMixed(\${$foreignEntityAsVar});

\$result = new Result();
\$resultFlag = true;

if (count(\${$localEntityAsVar}Ids) == 0 && count(\${$foreignEntityAsVar}Ids) == 0) {
    \$result->setResult(\$resultFlag);
    return \$result;
}

\$cond = \$this->getCond();

if (count(\${$localEntityAsVar}Ids) != 0) {
    \$cond->where(array('{$linkTableLocalColumn}' => \${$localEntityAsVar}Ids));
}

if (count(\${$foreignEntityAsVar}Ids) != 0) {
    \$cond->where(array('{$linkTableForeignColumn}' => \${$foreignEntityAsVar}Ids));
}

try {
    \$this->delete(\$cond->from('{$linkTableName}'));
} catch (\Exception \$e) {
    \$result->addChild('delete_link_failed', \$this->getGeneralErrorResult("Delete link {$localEntity} to {$foreignEntity} failed"));
    \$resultFlag = false;
}

\$result->setResult(\$resultFlag);
return \$result;
EOS
            );
        } else {
            $method->setBody(<<<EOS
    return {$foreignTableAsCamelCase}Model::getInstance()->deleteLink{$foreignEntityAsCamelCase}To{$localEntityAsCamelCase}(\${$foreignEntityAsVar}, \${$localEntityAsVar});
EOS
            );

        }

        return $method;

    }

    /**
     * @param AbstractLink $link
     * @return MethodGenerator
     */
    protected function getLinkMethodWithoutLinkTable(AbstractLink $link)
    {
        $localEntity = $link->getLocalEntity();
        $localEntityAsVar = $link->getLocalEntityAsVar();
        $localEntityAsCamelCase = $link->getLocalEntityAsCamelCase();

        $foreignTableAsCamelCase = $link->getForeignTable()->getNameAsCamelCase();
        $foreignEntity = $link->getForeignEntity();
        $foreignEntityAsVar = $link->getForeignEntityAsVar();
        $foreignEntityAsCamelCase = $link->getForeignEntityAsCamelCase();

        $localColumn = $link->getLocalColumn()->getName();

        $tags = array(
            array(
                'name'        => 'param',
                'description' => 'mixed $' . $localEntityAsVar
            ),

            array(
                'name'        => 'param',
                'description' => 'mixed $' . $foreignEntityAsVar
            ),

            array(
                'name'        => 'param',
                'description' => "\$isAppend Если false, сначала очищяются по {$localEntityAsVar}, потом добавляются"
            ),

            array(
                'name'        => 'return',
                'description' => '\Model\Result\Result'
            ),
        );

        $docblock = new DocBlockGenerator('Привязать ' . $localEntityAsCamelCase . ' к ' . $foreignEntityAsCamelCase);
        $docblock->setTags($tags);

        $method = new MethodGenerator();
        $method->setName('link' . $localEntityAsCamelCase . 'To' . $foreignEntityAsCamelCase);
        $method->setParameter(new ParameterGenerator($localEntityAsVar, 'mixed'));
        $method->setParameter(new ParameterGenerator($foreignEntityAsVar, 'mixed'));
        $method->setParameter(new ParameterGenerator('isAppend', 'mixed', true));
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setDocBlock($docblock);

        if ($link->isDirect()) {
            $method->setBody(<<<EOS
\${$localEntityAsVar}Ids = array_unique({$localEntityAsCamelCase}Model::getInstance()->getIdsFromMixed(\${$localEntityAsVar}));

\$result = new Result();
\$resultFlag = true;

if (count(\${$localEntityAsVar}Ids) == 0) {
    \$result->setResult(\$resultFlag);
    return \$result;
}

// Если связь нужно обновить, то есть удалить старые связи
// @TODO не должно работать когда связь обязательная
if (!\$isAppend) {
    try {
        \$this->deleteLink{$localEntityAsCamelCase}To{$foreignEntityAsCamelCase}(\${$localEntityAsVar}Ids);
    } catch (\Exception \$e) {
        \$result->addChild('delete_existed_link_failed', \$this->getGeneralErrorResult("Delete existed link {$localEntity} to {$foreignEntity} failed"));
        \$result->setResult(false);
        return \$result;
    }
}

\${$foreignEntityAsVar}Ids = {$foreignTableAsCamelCase}Model::getInstance()->getIdsFromMixed(\${$foreignEntityAsVar});
\${$foreignEntityAsVar}Ids = array_unique(\${$foreignEntityAsVar}Ids);

if (!\${$foreignEntityAsVar}Ids) {
    \$result->setResult(\$resultFlag);
    return \$result;
}

\$sql = "UPDATE `" . \$this->getRawName() . "` SET {$localColumn} = :{$localColumn} WHERE `id` = :id";
\$stmt = \$this->getDb()->prepare(\$sql);

foreach (\${$localEntityAsVar}Ids as \${$localEntityAsVar}Id) {
    foreach (\${$foreignEntityAsVar}Ids as \${$foreignEntityAsVar}Id) {
        try {
            \$stmt->execute(array('id' => \${$localEntityAsVar}Id, '{$localColumn}' => \${$foreignEntityAsVar}Id));
        } catch (\Exception \$e) {
            \$result->addChild('add_link_failed', \$this->getGeneralErrorResult("Add link {$localEntity} to {$foreignEntity} failed"));
            \$resultFlag = false;
        }
    }
}

\$result->setResult(\$resultFlag);
return \$result;
EOS
            );
        } else {
            $method->setBody(<<<EOS
    return {$foreignTableAsCamelCase}Model::getInstance()->link{$foreignEntityAsCamelCase}To{$localEntityAsCamelCase}(\$$foreignEntityAsVar, \$$localEntityAsVar, \$isAppend);
EOS
            );
        }
        return $method;

    }

    /**
     * @param AbstractLink $link
     * @return MethodGenerator
     */
    protected function getDeleteLinkMethodWithoutLinkTable(AbstractLink $link)
    {
        $localEntity = $link->getLocalEntity();
        $localEntityAsVar = $link->getLocalEntityAsVar();
        $localEntityAsCamelCase = $link->getLocalEntityAsCamelCase();

        $foreignTableAsCamelCase = $link->getForeignTable()->getNameAsCamelCase();
        $foreignEntity = $link->getForeignEntity();
        $foreignEntityAsVar = $link->getForeignEntityAsVar();
        $foreignEntityAsCamelCase = $link->getForeignEntityAsCamelCase();

        $localColumn = $link->getLocalColumn()->getName();
        $foreignColumn = $link->getForeignColumn()->getName();

        $tags = array(
            array(
                'name'        => 'param',
                'description' => 'mixed $' . $localEntityAsVar
            ),
            array(
                'name'        => 'param',
                'description' => 'mixed $' . $foreignEntityAsVar
            ),
            array(
                'name'        => 'return',
                'description' => '\Model\Result\Result'
            ),
        );

        $docblock = new DocBlockGenerator('Отвязать ' . $localEntityAsCamelCase . ' от ' . $foreignEntityAsCamelCase);
        $docblock->setTags($tags);

        $nullValue = new ValueGenerator('array()', ValueGenerator::TYPE_CONSTANT);
        $method = new MethodGenerator();
        $method->setName('deleteLink' . $localEntityAsCamelCase . 'To' . $foreignEntityAsCamelCase);
        $method->setParameter(new ParameterGenerator($localEntityAsVar, 'mixed', $nullValue));
        $method->setParameter(new ParameterGenerator($foreignEntityAsVar, 'mixed', $nullValue));
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setDocBlock($docblock);

        if ($link->isDirect()) {

            $method->setBody(<<<EOS
\${$localEntityAsVar}Ids = array_unique(\$this->getIdsFromMixed(\${$localEntityAsVar}));
\${$foreignEntityAsVar}Ids = array_unique({$foreignTableAsCamelCase}Model::getInstance()->getIdsFromMixed(\${$foreignEntityAsVar}));

\$result = new Result();
\$resultFlag = true;

if (count(\${$localEntityAsVar}Ids) == 0 && count(\${$foreignEntityAsVar}Ids) == 0) {
    \$result->setResult(\$resultFlag);
    return \$result;
}

\$cond = array();
if (count(\${$localEntityAsVar}Ids) != 0) {
    \$cond['{$foreignColumn}'] = \${$localEntityAsVar}Ids;
}

if (count(\${$foreignEntityAsVar}Ids) != 0) {
    \$cond['{$localColumn}'] = \${$foreignEntityAsVar}Ids;
}

try {
    \$this->getDb()->update(\$this->getRawName(), array('{$localColumn}' => null), \$cond);
} catch (\Exception \$e) {
    \$result->addChild('delete_link_failed', \$this->getGeneralErrorResult("Delete link {$localEntity} to {$foreignEntity} failed"));
    \$resultFlag = false;
}

\$result->setResult(\$resultFlag);
return \$result;
EOS
            );
        } else {
            $method->setBody(<<<EOS
return {$foreignTableAsCamelCase}Model::getInstance()->deleteLink{$foreignEntityAsCamelCase}To{$localEntityAsCamelCase}(\${$foreignEntityAsVar}, \${$localEntityAsVar});
EOS
            );

        }

        return $method;

    }

}