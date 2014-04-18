<?php

namespace Model\Generator\Part;

use Model\Stdlib\ArrayUtils;
use Model\Cluster\Schema\Table\Column;
use Model\Code\Generator\FileGenerator;
use Model\Generator\Log;

/**
 * Абстрактный класс для элемента генератора
 *
 * @category   Model
 * @package    Model_Generator
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
abstract class AbstractPart implements PartInterface
{
	const PART_MODEL                 = 'Model\Generator\Part\Plugin\Model\ModelInterface';
    const PART_FRONT_MODEL           = 'Model\Generator\Part\Plugin\FrontModel\FrontModelInterface';

	const PART_ENTITY                = 'Model\Generator\Part\Plugin\Entity\EntityInterface';
	const PART_FRONT_ENTITY          = 'Model\Generator\Part\Plugin\FrontEntity\FrontEntityInterface';

  	const PART_COLLECTION            = 'Model\Generator\Part\Plugin\Collection\CollectionInterface';
	const PART_FRONT_COLLECTION      = 'Model\Generator\Part\Plugin\FrontCollection\FrontCollectionInterface';

	const PART_COND                  = 'Model\Generator\Part\Plugin\Cond\CondInterface';
    const PART_FRONT_COND            = 'Model\Generator\Part\Plugin\FrontCond\FrontCondInterface';

	const RUNTIME_PRE  = 'preRun';
	const RUNTIME_POST = 'postRun';

    /**
     * @var string|null
     */
    protected $outputFilename = null;

	/**
	 * @var \Model\Cluster\Schema\Table
	 */
	protected $_table;

    /**
     * @var FileGenerator
     */
    private $file;

	/**
	 * Плагинчеги
	 * @var array
	 */
	protected static $_plugins = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @param FileGenerator $file
     */
    public function setFile(FileGenerator $file)
    {
        $this->file = $file;
    }

    /**
     * @return FileGenerator
     */
    public function getFile()
    {
        return $this->file;
    }

    protected static function _getPluginPath($part, $plugin)
    {
        $partInfoView = str_replace('_', '/', str_replace(array('Model_Builder_Part_Plugin_', '_Interface'), '', $part));
        return $partInfoView . '/' . $plugin->getName();
    }

	protected function _runPlugins($part, $runTime)
	{
		foreach ($this->getPlugins($part) as $plugin) {
			if ($plugin instanceof $part) {
				$args = array($this);
				$_args = func_get_args();

				if (func_num_args() > 2) {
					for($i=2; $i<func_num_args(); $i++) {
						$args[] = $_args[$i];
					}
				}

				Log::info("Run plugin " . self::_getPluginPath($part, $plugin) . '::' . $runTime);
				call_user_func_array(array($plugin, $runTime), $args);
			}
		}
	}

	/**
	 * Установить таблицу
	 *
	 * @param $table \Model\Cluster\Schema\Table
	 */
	protected function _setTable(\Model\Cluster\Schema\Table $table)
	{
		$this->_table = $table;
	}

	/**
	 * Получить таблицу
	 *
	 * @return \Model\Cluster\Schema\Table
	 */
	public function getTable()
	{
		return $this->_table;
	}

    /**
     *
     * @param \Model\Generator\Part\Plugin\PluginInterface $plugin
     * @param null                                         $part
     * @throws \Exception
     * @return void
     */

	public static function addPlugin(\Model\Generator\Part\Plugin\PluginInterface $plugin, $part = null)
	{
        if (!$part) {
           $reflection = new \ReflectionClass(get_class());
            $constants = $reflection->getConstants();

            foreach ($constants as $k => &$v) {
                if (substr($k, 0, 5) != 'PART_') {
                    continue;
                }
                if ($plugin instanceof $v && (!$part || strlen($part) < strlen($v))) {
                    $part = $v;
                }
            }
        }

        if (!$part) {
            throw new \Exception('Wrong plugin ' . $plugin->getName() . ' interface');
        }
        if (isset(self::$_plugins[$part][$plugin->getName()])) {
            throw new \Exception('Plugin ' . self::_getPluginPath($part, $plugin) . ' is already registred');
        }
		self::$_plugins[$part][$plugin->getName()] = $plugin;
	}

	/**
	 * Получить список плагинов
	 *
	 * @param string $part
	 * @return array of Model_Builder_Part_Plugin
	 */
	public function getPlugins($part)
	{
		return array_key_exists($part, self::$_plugins) ? self::$_plugins[$part] : array();
	}

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = (array)$options;
    }

    public function getOutputFilename()
    {
        if ($filename = $this->getOption('output_filename')) {
            return $filename;
        }

        return $this->outputFilename;
    }

    /**
     *
     * @param      $name
     * @param null $default
     * @return array
     */
    public function getOption($name, $default = null)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        return $default;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function getColumntConfig(Column $column)
    {
        $config = $this->getOption('config');
        $configFields =  (isset($config['fields'])) ? $config['fields'] : array();

        $result = array();
        foreach ($configFields as $configField) {
            if (isset($configField['match'])) {

                $isMatched = false;
                foreach ($configField['match'] as $match) {
                    $isMatched = false;
                    if (isset($match['type'])) {
                        $matchTypes = is_array($match['type']) ? $match['type'] : array($match['type']);
                        $isMatched = in_array($column->getColumnType(), $matchTypes);
                    }

                    if (isset($match['regexp'])) {
                        $isMatched = $isMatched && preg_match($match['regexp'], $column->getFullName());
                    }

                    $columnLength = $column->getCharacterMaximumLength() ? $column->getCharacterMaximumLength() : $column->getNumericPrecision();

                    if ($isMatched && isset($match['length'])) {
                        foreach ($match['length'] as $operation => $lengthMatch) {
                            $operation = preg_replace('#\s+#', '', $operation);
                            switch ($operation) {
                                case '<':
                                    $isMatched = ($columnLength < $lengthMatch);
                                    break;
                                case '>':
                                    $isMatched = ($columnLength > $lengthMatch);
                                    break;
                                case '>=':
                                    $isMatched = ($columnLength >= $lengthMatch);
                                    break;
                                case '<=':
                                    $isMatched = ($columnLength <= $lengthMatch);
                                    break;
                                case '==':
                                    $isMatched = ($columnLength == $lengthMatch);
                                    break;
                                case '=':
                                    $isMatched = ($columnLength == $lengthMatch);
                                    break;
                                default:
                                    $isMatched = false;
                            }
                        }
                    }

                    if ($isMatched) {
                        break;
                    }
                }

                if ($isMatched) {
                    $result = ArrayUtils::merge($result, $configField);
                }
            }
        }

        return $result;
    }

}