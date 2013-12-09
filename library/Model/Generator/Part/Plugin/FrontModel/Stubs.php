<?php

namespace Model\Generator\Part\Plugin\FrontModel;

use Model\Generator\Part\PartInterface;
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
 * @version    SVN: $Id$
 */
class Stubs extends AbstractFrontModel
{
    public function __construct()
    {
        $this->_setName('Stubs');
    }

    public function preRun(PartInterface $part)
    { }

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
        $tableNameAsCamelCase = $table->getNameAsCamelCase();

        $file->setUse('Model\\Cond\\' . $tableNameAsCamelCase . 'Cond', 'Cond');
        $file->setUse('Model\\Entity\\' . $tableNameAsCamelCase . 'Entity');
        $file->setUse('Model\\Collection\\' . $tableNameAsCamelCase . 'Collection');
        $file->setUse('Model\\Result\\Result');

        $this->defaultStub($file);
        $this->setupCascadeRulesStub($file);
        $this->setupFilterRulesStub($file);
        $this->validatorStub($file);
        $this->prepareStub($file);
        $this->viewStub($part);

        return $file;
    }

    protected function defaultStub($file)
    {
        $docblock = new DocBlockGenerator('Инициализация значений по-умолчанию при добавлении записей

Данные по-умолчанию накладываются только при добавлении данных.
Если нужно сделать, что бы при обновлении данных какое-то поле обновлялось по алгоритму,
то можно вопользоваться методом: ---

ПОМНИ: Сначала данные по-умолчанию, потом касдад!

Поочередность при добавлении данных:
- накладываем значения по-умолчанию;
- применяем касдад для значений параметров;
- фильтруем данные;
- проверяем данные;
- отдаем данные базе данных.

В этом методе инициализируем данные по-умолчанию,
чаще всего они берутся из описания таблицы, но их
можно расширить или переопределить

@see https://github.com/meniam/model#-chapter-52-%D0%97%D0%BD%D0%B0%D1%87%D0%B5%D0%BD%D0%B8%D1%8F-%D0%BF%D0%BE-%D1%83%D0%BC%D0%BE%D0%BB%D1%87%D0%B0%D0%BD%D0%B8%D1%8E
');
//$docblock->setTags($tags);

        $method = new MethodGenerator();
        $method->setName('setupDefaultRules');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PROTECTED);
        $method->setFinal(false);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS

// \$this->setDefaultRule('test_field', 'Значение по-умолчанию');
// \$this->setDefaultRule('create_Date', date('Y-m-d H:i:s');
// \$this->setDefaultRule('status', 'active');
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }


    protected function setupFilterRulesStub($file)
    {
        $docblock = new DocBlockGenerator('Установка правил фильтрации полей

У фильтрации нет разделения на фильры при добавлении и фильтры при обновлении
Фильтры едины для поля. Если нужны связки фильтров, то лучше это делать
за пределами модели и не усложнять логику :)

Для добавления фильтра к полю используется @method self::addFilterRule
Если нужно удалить или изменить правила фильтрации, то этих методов пока нет,
попросите меня их добавить: meniam@gmail.com
');

        $method = new MethodGenerator();
        $method->setName('setupFilterRules');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PROTECTED);
        $method->setFinal(false);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
// \$this->addFilterRule('field', Filter::getFilterInstance('App\Filter\Id'));
// \$this->addFilterRule('field', Filter::getFilterInstance('App\Filter\Null'));
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }


    protected function validatorStub($file)
    {
        $tags = array(
            array(
                'name'        => 'param',
                'description' => 'boolean $required true - при добавлении, в остальных случаях false'
            ),
            array(
                'name'        => 'return',
                'description' => 'void'
            ),
        );

        $docblock = new DocBlockGenerator('Инициализация правил валидации

Третий параметр у addValidatorRule это обязателен ли этот валидатор
при добавлении данных в базу.
');
        $docblock->setTags($tags);

        $p = new \Zend\Code\Generator\ParameterGenerator('required');
        $p->setDefaultValue(true);
        $params[] = $p;

        $method = new MethodGenerator();
        $method->setName('setupValidatorRules');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PROTECTED);
        $method->setFinal(false);
        $method->setParameters($params);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
// \$this->addValidatorRule('field', Validator::getValidatorInstance('Zend\Filter\Int'), true or false);
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }


    protected function prepareStub(\Zend\Code\Generator\FileGenerator $file)
    {
        $p = new \Zend\Code\Generator\ParameterGenerator('data');
        $p->setType('array');
        $p->setDefaultValue(null);
        $params[] = $p;

        $p = new \Zend\Code\Generator\ParameterGenerator('cond');
        $p->setType('\Model\Cond\AbstractCond');
        $p->setDefaultValue(null);
        $params[] = $p;

        $docblock = new DocBlockGenerator('Хук вызываемый перед обработкой данных при выборках

        ОСТОРОЖНО! Данные будут обрабатываться при каждой выборке!

        ');

        $method = new MethodGenerator();
        $method->setName('beforePrepare');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PROTECTED);
        $method->setFinal(false);
        $method->setDocBlock($docblock);
        $method->setParameters($params);

        $method->setBody(<<<EOS
return \$data;
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);

        $docblock = new DocBlockGenerator('Хук вызываемый после обработки данных при выборках

        ОСТОРОЖНО! Данные будут обрабатываться при каждой выборке!

        ');

        $method = new MethodGenerator();
        $method->setName('afterPrepare');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PROTECTED);
        $method->setFinal(false);
        $method->setDocBlock($docblock);
        $method->setParameters($params);
        $method->setBody(<<<EOS
return \$data;
EOS
        );


        $file->getClass()->addMethodFromGenerator($method);


        $docblock = new DocBlockGenerator('Хук вызываемый перед обработкой данных при добавлении или изменении

Подобным образом работают следующие методы:
 - beforePrepareOnAdd - выполняется только перед добавлением
 - beforePrepareOnUpdate - выполняется только перед обновлением данных
');

        $method = new MethodGenerator();
        $method->setName('beforePrepareOnAddOrUpdate');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PROTECTED);
        $method->setFinal(false);
        $method->setDocBlock($docblock);
        $method->setParameters($params);

        $method->setBody(<<<EOS
// \$data['modify_date'] = date('Y-m-d H:i:s');
//
// if (array_key_exists('phrase', \$data)) {
//     \$data['word_count'] = count(explode(' ', \$data['phrase']));
// };
return \$data;
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);

        $docblock = new DocBlockGenerator('Хук вызываемый после обработки данных при добавлении или изменении

Подобным образом работают следующие методы:
 - afterPrepareOnAdd - выполняется после обработки данных только при добавлении
 - afterPrepareOnUpdate - выполняется после обработки данных только при обновлении
        ');

        $method = new MethodGenerator();
        $method->setName('afterPrepareOnAddOrUpdate');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PROTECTED);
        $method->setFinal(false);
        $method->setDocBlock($docblock);
        $method->setParameters($params);

        $file->getClass()->addMethodFromGenerator($method);
    }

    protected function setupCascadeRulesStub($file)
    {
        $docblock = new DocBlockGenerator('Каскад значений для полей
');

        $method = new MethodGenerator();
        $method->setName('setupFilterCascadeRules');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PROTECTED);
        $method->setFinal(false);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
// Эти фильтры работают только при добавлении, для фильтров на update используйте updateFilterCascade
// Если имя прийдет пустое попробовать взять по-очереди из h1, title, meta_title

// Третье значение отвечает за каскад при добавлении: true - использовать при обновлении

// \$this->addFilterCascadeParent('name', 'h1', true);
// \$this->addFilterCascadeParent('name', 'title', true);
// \$this->addFilterCascadeParent('name', 'meta_title', true);

// \$this->addFilterCascadeParent('h1', array('name', 'title', 'meta_title'), true);

EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }

    protected function viewStub($part)
    {
        /**
         * @var $part \Model\Generator\Part\Model
         */

        /**
         * @var $file \Zend\Code\Generator\FileGenerator
         */
        $file = $part->getFile();
        $table = $part->getTable();
        $tableNameAsCamelCase = $table->getNameAsCamelCase();
        $entity = $tableNameAsCamelCase . 'Entity';
        $collection = $tableNameAsCamelCase . 'Collection';


        $p = new \Zend\Code\Generator\ParameterGenerator('cond');
        $p->setType('Cond');
        $p->setDefaultValue(null);
        $params[] = $p;

        $docblock = new DocBlockGenerator('Получить объект условий в виде представления \'Extended\'

$param Cond $cond
$return Cond
        ');

        $method = new MethodGenerator();
        $method->setName('getCondAsExtendedView');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setFinal(false);
        $method->setDocBlock($docblock);
        $method->setParameters($params);

        $method->setBody(<<<EOS
\$cond = \$this->prepareCond(\$cond);
\$cond->where(array('status' => 'active'));
return \$cond;
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);

        $p = new \Zend\Code\Generator\ParameterGenerator('cond');
        $p->setType('Cond');
        $p->setDefaultValue(null);
        $params[] = $p;

        $docblock = new DocBlockGenerator('Получить элемент в виде представления \'Extended\'

@param Cond $cond
@return ' . $entity);

        $method = new MethodGenerator();
        $method->setName('getAsExtendedView');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setFinal(false);
        $method->setDocBlock($docblock);
        $method->setParameters($params);

        $method->setBody(<<<EOS
\$cond = \$this->getCondAsExtendedView(\$cond);
return \$this->get(\$cond);
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);


        $p = new \Zend\Code\Generator\ParameterGenerator('cond');
        $p->setType('Cond');
        $p->setDefaultValue(null);
        $params[] = $p;

        $docblock = new DocBlockGenerator('Получить коллекцию в виде представления \'Extended\'

@param Cond $cond
@return ' . $collection);

        $method = new MethodGenerator();
        $method->setName('getCollectionAsExtendedView');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PUBLIC);
        $method->setFinal(false);
        $method->setDocBlock($docblock);
        $method->setParameters($params);

        $method->setBody(<<<EOS
\$cond = \$this->getCondAsExtendedView(\$cond);
return \$this->getCollection(\$cond);
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);

   }

}