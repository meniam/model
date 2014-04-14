<?php

namespace Model;

use Model\Exception\ErrorException;
use Model\Generator\Part\AbstractPart;
use Model\Db\Mysql as DbAdapter;

use Model\Cluster\Schema\Table;
use Model\Generator\Part\Cond;
use Model\Generator\Part\Entity;
use Model\Generator\Part\FrontCond;
use Model\Generator\Part\FrontEntity;
use \Model\Generator\Part\Collection;
use \Model\Generator\Part\FrontCollection;
use Model\Generator\Part\FrontModel;
use Model\Generator\Part\Model;
use Model\Generator\Part\Plugin\Cond\JoinConst;

/**
 * Основной класс генератора
 *
 * @category   Model
 * @package    Model_Generator
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Generator
{
    /**
     * Название базы данных
     *
     * @var string
     */
    protected $_dbName;

    /**
     * @var Cluster
     */
    protected $cluster;

    /**
     * @var DbAdapter
     */
    protected $_db;

    /**
     * @var
     */
    protected $_outDir;

    protected $outDirArray = array();

    /**
     * @var
     */
    protected $_deployDir;

    /**
     * @var
     */
    private $sm;

	public function __construct($sm, $cluster, $outDir)
	{
        $this->sm = $sm;
        $this->cluster = $cluster;
		$this->_outDir = rtrim($outDir, '/');

		if (!is_dir($outDir) || !is_writeable($outDir)) {
			throw new ErrorException('Directory is not writable: ' . $outDir);
		}

        $this->outDirArray = array(
            AbstractPart::PART_FRONT_COLLECTION => $outDir . '/collections',
            AbstractPart::PART_COLLECTION       => $outDir . '/collections/abstract',

            AbstractPart::PART_FRONT_COND       => $outDir . '/cond',
            AbstractPart::PART_COND             => $outDir . '/cond/abstract',

            AbstractPart::PART_FRONT_ENTITY     => $outDir . '/entities',
            AbstractPart::PART_ENTITY           => $outDir . '/entities/abstract',

            AbstractPart::PART_FRONT_MODEL      => $outDir . '/',
            AbstractPart::PART_MODEL            => $outDir . '/abstract');

        foreach ($this->outDirArray as $dir) {
            @mkdir($dir, 0755, true);

            if (!is_dir($dir) || !is_readable($dir)) {
                throw new ErrorException('Directory is not writable: ' . $dir);
            }
        }
	}

    public function buildPart(Table $table, $part, $aliasName = null)
    {
        if ($part == 'front_cond') {
            $aliasList = $table->getAliasLinkList();
            if (!empty($aliasList) && !$aliasName) {
                foreach ($aliasList as $_aliasName => $tableName) {
                    $this->buildPart($table, $part, $_aliasName);
                }
            }
        }

        $partConst = constant('\\Model\\Generator\\Part\\AbstractPart::PART_' . strtoupper($part));
        $partAsCameCase = implode('', array_map('ucfirst', explode('_', $part)));
        if ($aliasName) {
            $aliasNameAsCamelCase = implode('', array_map('ucfirst', explode('_', $aliasName)));
        } else {
            $aliasNameAsCamelCase = $table->getNameAsCamelCase();
        }

        $partClass = '\\Model\\Generator\\Part\\' . $partAsCameCase;

        $outputFile = $this->outDirArray[$partConst];
        $outputFile = rtrim($outputFile, '/') . '/';
        if (preg_match('#abstract/?$#', $outputFile)) {
            $outputFile .= 'Abstract';
        }
        $outputFile .= str_replace('Front', '', $aliasNameAsCamelCase . $partAsCameCase) . '.php';
        $options = $aliasName ? array('alias' => $aliasName) : array();

        new $partClass($table, $this->getCluster(), $outputFile, $options);
        //$partObject->generate($options);
        if (!is_file($outputFile)) {
            echo $partClass . "- " ;
            echo $outputFile;
            echo "fuck";die;
        }
        //echo $outputFile . "\n";
    }


    /**
     * @param      $tableName
     * @param null $aliasName
     * @return bool
    public function buildPartCond($tableName, $aliasName = null)
    {
        $table = $this->getCluster()->getTable($tableName);
        if (!$table) {
            return false;
        }

        $tableNameAsCamelCase = $table->getNameAsCamelCase();

        if ($aliasName) {
            $file = $this->_outDir . '/cond/abstract/Abstract' . implode('', array_map('ucfirst', explode('_', $aliasName))) . 'Cond.php';
        } else {
            $file = $this->_outDir . '/cond/abstract/Abstract' . $tableNameAsCamelCase . 'Cond.php';
        }

        $cond = new Cond($table, $this->getCluster(), $file);
        $cond->generate(array('alias' => $aliasName));

        return true;
    }

    public function buildPartFrontCond($tableName, $aliasName = null)
    {
        $table = $this->getCluster()->getTable($tableName);
        $tableNameAsCamelCase = $table->getNameAsCamelCase();

        if ($aliasName) {
            $file = $this->_outDir . '/cond/' . implode('', array_map('ucfirst', explode('_', $aliasName))) . 'Cond.php';
        } else {
            $file = $this->_outDir . '/cond/' . $tableNameAsCamelCase . 'Cond.php';
        }

        new FrontCond($table, $this->getCluster(), $file, array('alias' => $aliasName));

        return true;
    }
     */

    /**
     * @throws \Exception
     */
    public function run()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/models.json'), true);

        if (isset($config['plugins'])) {
            foreach ($config['plugins'] as $partType => $pluginArray )
                if (isset($pluginArray['list'])) {
                    foreach ($pluginArray['list'] as $condPluginArray) {
                        if ($pluginName = (isset($condPluginArray['name']) ? $condPluginArray['name'] : null)) {
                            $partTypeAsCamelCalse = implode('', array_map('ucfirst', explode('_', $partType)));

                            $condPluginClassName = '\\Model\\Generator\\Part\\Plugin\\' . $partTypeAsCamelCalse . '\\' . $pluginName;
                            $partConst = constant('\\Model\\Generator\\Part\\AbstractPart::PART_' . strtoupper($partType));

                            AbstractPart::addPlugin(new $condPluginClassName(), $partConst);
                        }
                    }
                }
        }

        /**
         * Generate Alias cond
         */
        $classmap = '';
        foreach ($this->getCluster()->getTableList() as $table) {
            $aliasList = $table->getAliasLinkList();
            if (!empty($aliasList)) {
                foreach ($aliasList as $aliasName => $tableName) {
                    $this->buildPart($this->getCluster()->getTable($tableName), 'front_cond', $aliasName);
                    $aliasAsCamelCase = implode('', array_map('ucfirst', explode('_', $aliasName)));
                    $classmap .= "\t\t'Model\\\\Cond\\\\{$aliasAsCamelCase}Cond' => __DIR__ . '/cond/{$aliasAsCamelCase}Cond.php',\n";
                }
            }

            $this->buildPart($table, 'cond');
            $this->buildPart($table, 'front_cond');
/*            if ($table->getName() == 'tag') {
                print_r($aliasList);
                die;
            }
*/

            $this->buildPart($table, 'collection');
            $this->buildPart($table, 'front_collection');

            $this->buildPart($table, 'entity');
            $this->buildPart($table, 'front_entity');

            $this->buildPart($table, 'model');
            $this->buildPart($table, 'front_model');

            //$this->buildPartModel($table->getName());
            //$this->buildPartFrontModel($table->getName());
            //$this->buildPartCond($table->getName());
            //$this->buildPartFrontCond($table->getName());

            //$this->buildPartEntity($table->getName());
            //$this->buildPartFrontEntity($table->getName());

            //$this->buildPartCollection($table->getName());
            //$this->buildPartFrontCollection($table->getName());

            $tableNameAsCamelCase = $table->getNameAsCamelCase();
            $classmap .= "\t\t'Model\\\\Abstract{$tableNameAsCamelCase}Model' => __DIR__ . '/abstract/Abstract{$tableNameAsCamelCase}Model.php',\n";
            $classmap .= "\t\t'Model\\\\{$tableNameAsCamelCase}Model' => __DIR__ . '/{$tableNameAsCamelCase}Model.php',\n";
            $classmap .= "\t\t'Model\\\\Entity\\\\{$tableNameAsCamelCase}Entity' => __DIR__ . '/entities/{$tableNameAsCamelCase}Entity.php',\n";
            $classmap .= "\t\t'Model\\\\Entity\\\\Abstract{$tableNameAsCamelCase}Entity' => __DIR__ . '/entities/abstract/Abstract{$tableNameAsCamelCase}Entity.php',\n";
            $classmap .= "\t\t'Model\\\\Collection\\\\Abstract{$tableNameAsCamelCase}Collection' => __DIR__ . '/collections/abstract/Abstract{$tableNameAsCamelCase}Collection.php',\n";
            $classmap .= "\t\t'Model\\\\Collection\\\\{$tableNameAsCamelCase}Collection' => __DIR__ . '/collections/{$tableNameAsCamelCase}Collection.php',\n";
            $classmap .= "\t\t'Model\\\\Cond\\\\Abstract{$tableNameAsCamelCase}Cond' => __DIR__ . '/cond/abstract/Abstract{$tableNameAsCamelCase}Cond.php',\n";
            $classmap .= "\t\t'Model\\\\Cond\\\\{$tableNameAsCamelCase}Cond' => __DIR__ . '/cond/{$tableNameAsCamelCase}Cond.php',\n";
        }

        file_put_contents($this->_outDir . '/_autoload_classmap.php', "<?php\nreturn array(" . $classmap . ");\n");
    }

    public function deploy($outDir)
    {
        $this->_deployDir = rtrim($outDir, '/');

        if (!is_dir($outDir) || !is_writeable($outDir)) {
            throw new ErrorException ('Directory is not writable: ' . $outDir);
        }

        @mkdir($outDir . '/abstract');
        @mkdir($outDir . '/entities');
        @mkdir($outDir . '/entities/abstract');
        @mkdir($outDir . '/collections');
        @mkdir($outDir . '/collections/abstract');

        @mkdir($outDir . '/cond');
        @mkdir($outDir . '/cond/abstract');

        foreach (glob($this->_outDir . '/abstract/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/abstract/' . basename($filename);
            //echo $deployFile;
            copy($filename, $deployFile);
        }

        unlink($this->_deployDir . '/_autoload_classmap.php');
        foreach (glob($this->_outDir . '/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/' . basename($filename);
            //echo $deployFile;

            if (!is_file($deployFile)) {
                copy($filename, $deployFile);
            }
        }

        foreach (glob($this->_outDir . '/cond/abstract/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/cond/abstract/' . basename($filename);
            //echo $deployFile;
            copy($filename, $deployFile);
        }

        foreach (glob($this->_outDir . '/cond/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/cond/' . basename($filename);
            //echo $deployFile;

            if (!is_file($deployFile)) {
                copy($filename, $deployFile);
            }
        }

        foreach (glob($this->_outDir . '/collections/abstract/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/collections/abstract/' . basename($filename);
            //echo $deployFile;
            copy($filename, $deployFile);
        }

        foreach (glob($this->_outDir . '/collections/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/collections/' . basename($filename);
            //echo $deployFile;

            if (!is_file($deployFile)) {
                copy($filename, $deployFile);
            }
        }

        foreach (glob($this->_outDir . '/entities/abstract/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/entities/abstract/' . basename($filename);
            //echo $deployFile;
            copy($filename, $deployFile);
        }

        foreach (glob($this->_outDir . '/entities/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/entities/' . basename($filename);
            //echo $deployFile;

            if (!is_file($deployFile)) {
                copy($filename, $deployFile);
            }
        }
    }

    /**
     * @return Cluster
     */
    public function getCluster()
    {
        return $this->cluster;
    }

    /**
     * @return string
     */
    public function toXml()
    {
        $xml = $this->getCluster()->toXml();
        $xml = simplexml_load_string($xml);
        return $xml->asXml();
    }
}