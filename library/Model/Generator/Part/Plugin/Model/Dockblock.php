<?php

namespace Model\Generator\Part\Plugin\Model;

use Model\Generator\Part\PartInterface;
use Model\Cluster\Schema\Table\Link\AbstractLink;
use Model\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\AbstractMemberGenerator;

class Dockblock extends AbstractModel
{
	public function __construct()
	{
 		$this->_setName('Dockblock');
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


        $tableNameAsCamelCase = $part->getTable()->getNameAsCamelCase();
        //$tableComment = $part->getTable()->getComment();

        $tags = array(
                             array(
                                 'name'        => 'version',
                                 'description' => '$Rev:$',
                             ),
                             array(
                                 'name'        => 'license',
                                 'description' => 'MIT',
                             ),
                             array(
                                 'name'        => 'author',
                                 'description' => 'Model_Generator',
                             ),
                             array(
                                 'name'        => 'author',
                                 'description' => 'Eugene Myazin <meniam@gmail.com>',
                             ),
                             array(
                                 'name'        => 'author',
                                 'description' => 'Mikhail Rybalka <ruspanzer@gmail.ru>',
                             ),
                             array(
                                 'name'        => 'author',
                                 'description' => 'Vadim Slutsky <2trustnik@gmail.com>',
                             ),
                             array(
                                 'name'        => 'author',
                                 'description' => 'Anton Sedyshev <madtoha@yandex.ru>',
                             ),
                            array(
                                 'name' => 'method',
                                'description' => $tableNameAsCamelCase . "Cond getCond() getCond()(Cond \$cond = null) get condition"
                            )

                    );

        if ($file->getClass()->getDocblock()) {
            $file->getClass()->getDocblock()->setTags($tags);
        } else {
            $docblock = new DocBlockGenerator('Abstract model ' .  $tableNameAsCamelCase);
            $docblock->setTags($tags);
            $file->getClass()->setDocblock($docblock);
        }
    }
}