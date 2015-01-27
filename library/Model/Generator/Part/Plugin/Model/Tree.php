<?php

namespace Model\Generator\Part\Plugin\Model;

use Model\Generator\Part\PartInterface;

/**
 * Плагин для генерации свойства relation
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Tree extends AbstractModel
{
    public function __construct()
    {
        $this->_setName('Tree');
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
        $table = $part->getTable();


        if (!$table->getColumn('parent_id')) {
            return;
        }
    }

}
