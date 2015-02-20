<?php

namespace Model\Generator\Part\Plugin\Entity;
use Model\Generator\Part\PartInterface;
use Model\Code\Generator\DocBlockGenerator;
use Model\Cluster\Schema\Table\Column;

class Dockblock extends AbstractEntity
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
                    );

        if ($file->getClass()->getDocblock()) {
            $file->getClass()->getDocblock()->setTags($tags);
        } else {
            $docblock = new DocBlockGenerator('Entity ' .  $tableNameAsCamelCase);
            $docblock->setTags($tags);
            $file->getClass()->setDocblock($docblock);
        }
    }
}