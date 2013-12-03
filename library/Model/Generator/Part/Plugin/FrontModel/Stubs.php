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

        $this->defaultStub($file);
        $this->setupFilterRulesStub($file);
        $this->setupCascadeRulesStub($file);
        $this->validatorStub($file);

        return $file;
    }

    protected function defaultStub($file)
    {
        /*$tags = array(
            array(
                'name'        => 'return',
                'description' => 'void'
            ),
        );*/

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
');

        $method = new MethodGenerator();
        $method->setName('setupFilterRules');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PROTECTED);
        $method->setFinal(false);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
// \$this->filterRules['field'][] = Filter::getFilterInstance('App\Filter\Id');
EOS
        );

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

// \$this->addFilterCascadeRules['name'] => array('h1', 'title', 'meta_title');
// \$this->addFilterCascadeRules['h1'] => array('name', 'title', 'meta_title');
// \$this->addFilterCascadeRules['title'] => array('name', 'h1', 'title');
// \$this->addFilterCascadeRules['meta_title'] => array('name', 'h1', 'meta_title');
// \$this->addFilterCascadeRules['slug'] => array('name', 'h1', 'title', 'meta_title');

EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }

    protected function validatorStub($file)
    {
        $tags = array(
            array(
                'name'        => 'return',
                'description' => 'void'
            ),
        );

        $docblock = new DocBlockGenerator('Инициализация правил валидации при добавлении');
        $docblock->setTags($tags);

        $method = new MethodGenerator();
        $method->setName('setupValidatorRulesOnAdd');
        $method->setVisibility(AbstractMemberGenerator::VISIBILITY_PROTECTED);
        $method->setFinal(false);
        $method->setDocBlock($docblock);

        $method->setBody(<<<EOS
/**
 * В этом методе устанавливаются валидаторы,
   на поля при добавлении
 */
EOS
        );

        $file->getClass()->addMethodFromGenerator($method);
    }

}