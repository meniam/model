<?php

namespace Model\Generator\Part;

/**
 * Интерфейс для элемента генератора
 *
 * @category   Model
 * @package    Model_Generator
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
interface PartInterface
{
	public function __construct(\Model\Cluster\Schema\Table $table, \Model\Cluster $cluster, $outputFilename = null);
}