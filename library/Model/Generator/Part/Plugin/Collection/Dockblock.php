<?php

namespace Model\Generator\Part\Plugin\Collection;
use Model\Generator\Part\PartInterface;

class Dockblock extends AbstractCollection
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
         * @var $file \Zend\Code\Generator\FileGenerator
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
                                 'description' => 'New BSD',
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
                                 'description' => 'Vadim Slutsky <2trustnik@gmail.com>',
                             ),
                             array(
                                 'name'        => 'author',
                                 'description' => 'Anton Sedyshev <madtoha@yandex.ru>',
                             ),
                    );

        if ($file->getClass()->getDocblock()) {
            $file->getClass()->getDocblock()->setTags($tags);
        } else {
            $docblock = new \Zend\Code\Generator\DocBlockGenerator('Абстракция набора данных ' .  $tableNameAsCamelCase);
            $docblock->setTags($tags);
            $file->getClass()->setDocblock($docblock);
        }
    }
}