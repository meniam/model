<?php

namespace Model\Generator;

use Model\Cluster;
use Model\Cluster\Schema;
use Model\Cluster\Schema\Table;

use Model\Config\Config;
use Model\Db\Mysql;
use Model\Exception\ErrorException;
use Model\Db\Mysql as DbAdapter;

use Model\Generator\Part\AbstractPart;
use Model\Generator\Part\Entity;

use Zend\Console\ColorInterface;
use Zend\Console\Console;

/**
 * Base generator class
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
     * @var Cluster
     */
    protected $cluster;

    /**
     * @var
     */
    protected $outDir;

    /**
     * @var array
     */
    protected $outDirArray = array();

    /**
     * @var
     */
    protected $deployDir;

    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $outputDir = $config->getParameter('output-dir');
        if (!$outputDir) {
            $console = Console::getInstance();
            $console->write("Unknown output dir. Use ./models --help\n", ColorInterface::RED);
            exit();
        }

        if (!is_dir($outputDir) || !is_writeable($outputDir)) {
            $console = Console::getInstance();
            $console->write("Unknown output dir not exists or not writable. Use ./models --help\n", ColorInterface::RED);
            exit();
        }
        $this->setOutDir($outputDir);

        // Read Db Configuration
        $dsn     = $config->getParameter('dsn', '');
        $user = $config->getParameter('user', 'root');
        $password = $config->getParameter('password', '');

        $db  = new Mysql($dsn, $user, $password);

        $this->cluster = new Cluster();
        $this->cluster->addSchema((new Schema($db))->init());

        $configPath = $config->getParameter('config', __DIR__ . '/models.json');
        if (!is_file($configPath) || !is_readable($configPath)) {
            $console = Console::getInstance();
            $console->write("Config file not exists or not readable. Use ./models --help\n", ColorInterface::RED);
            exit();
        }
        $generatorConfig = json_decode(file_get_contents($configPath), true);
        if (!$generatorConfig) {
            $console = Console::getInstance();
            $console->write("Bad config file. Use ./models --help\n", ColorInterface::RED);
            exit();
        }
        $this->setGeneratorConfig($generatorConfig);

        // Register plugins
        $plugins = isset($generatorConfig['plugins']) ? $generatorConfig['plugins'] : array();
        if (!empty($plugins)) {
            $this->registerPlugins($plugins);
        }

        $this->config = $config;
    }

    private function setOutDir($outDir)
    {
        $this->outDir = rtrim($outDir, '/');

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

    private $generatorConfig = array();

    /**
     * @return array
     */
    public function getGeneratorConfig()
    {
        return $this->generatorConfig;
    }

    /**
     * @param array $generatorConfig
     */
    public function setGeneratorConfig($generatorConfig)
    {
        $this->generatorConfig = $generatorConfig;
    }

    /**
     * @param Table $table
     * @param       $part
     * @param null  $aliasName
     */
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
        $options = $aliasName ? array('alias' => $aliasName, 'config' => $this->getGeneratorConfig()) : array('config' => $this->getGeneratorConfig());

        new $partClass($table, $this->getCluster(), $outputFile, $options);
    }

    /**
     * @param $plugins
     * @throws \Exception
     */
    public function registerPlugins($plugins)
    {
        foreach ($plugins as $partType => $pluginArray) {
            if (isset($pluginArray['list'])) {
                foreach ($pluginArray['list'] as $condPluginArray) {
                    if ($pluginName = (isset($condPluginArray['name']) ? $condPluginArray['name'] : null)) {
                        $partTypeAsCamelCase = implode('', array_map('ucfirst', explode('_', $partType)));

                        $condPluginClassName = '\\Model\\Generator\\Part\\Plugin\\' . $partTypeAsCamelCase . '\\' . $pluginName;
                        $partConst = constant('\\Model\\Generator\\Part\\AbstractPart::PART_' . strtoupper($partType));

                        AbstractPart::addPlugin(new $condPluginClassName(), $partConst);
                    }
                }
            }
        }
    }

    /**
     * @return null
     * @throws ErrorException
     * @throws \Exception
     */
    public function run()
    {
        /**
         * Generate Alias cond
         */
        $classMap = '';
        foreach ($this->getCluster()->getTableList() as $table) {
            if (substr($table->getName(), 0, 1) == '_' || substr($table->getName(), -5) == '_link') {
                continue;
            }

            $aliasList = $table->getAliasLinkList();
            if (!empty($aliasList)) {
                foreach ($aliasList as $aliasName => $tableName) {
                    $this->buildPart($this->getCluster()->getTable($tableName), 'front_cond', $aliasName);
                    $aliasAsCamelCase = implode('', array_map('ucfirst', explode('_', $aliasName)));
                    $classMap .= "\t\t'Model\\\\Cond\\\\{$aliasAsCamelCase}Cond' => __DIR__ . '/cond/{$aliasAsCamelCase}Cond.php',\n";
                }
            }

            $this->buildPart($table, 'cond');
            $this->buildPart($table, 'front_cond');
            $this->buildPart($table, 'collection');
            $this->buildPart($table, 'front_collection');
            $this->buildPart($table, 'entity');
            $this->buildPart($table, 'front_entity');
            $this->buildPart($table, 'model');
            $this->buildPart($table, 'front_model');

            $tableNameAsCamelCase = $table->getNameAsCamelCase();
            $classMap .= "\t\t'Model\\\\Abstract{$tableNameAsCamelCase}Model' => __DIR__ . '/abstract/Abstract{$tableNameAsCamelCase}Model.php',\n";
            $classMap .= "\t\t'Model\\\\{$tableNameAsCamelCase}Model' => __DIR__ . '/{$tableNameAsCamelCase}Model.php',\n";
            $classMap .= "\t\t'Model\\\\Entity\\\\{$tableNameAsCamelCase}Entity' => __DIR__ . '/entities/{$tableNameAsCamelCase}Entity.php',\n";
            $classMap .= "\t\t'Model\\\\Entity\\\\Abstract{$tableNameAsCamelCase}Entity' => __DIR__ . '/entities/abstract/Abstract{$tableNameAsCamelCase}Entity.php',\n";
            $classMap .= "\t\t'Model\\\\Collection\\\\Abstract{$tableNameAsCamelCase}Collection' => __DIR__ . '/collections/abstract/Abstract{$tableNameAsCamelCase}Collection.php',\n";
            $classMap .= "\t\t'Model\\\\Collection\\\\{$tableNameAsCamelCase}Collection' => __DIR__ . '/collections/{$tableNameAsCamelCase}Collection.php',\n";
            $classMap .= "\t\t'Model\\\\Cond\\\\Abstract{$tableNameAsCamelCase}Cond' => __DIR__ . '/cond/abstract/Abstract{$tableNameAsCamelCase}Cond.php',\n";
            $classMap .= "\t\t'Model\\\\Cond\\\\{$tableNameAsCamelCase}Cond' => __DIR__ . '/cond/{$tableNameAsCamelCase}Cond.php',\n";
        }

        file_put_contents($this->outDir . '/_autoload_classmap.php', "<?php\nreturn array(\n" . $classMap . ");\n");

        if ($deployDir = $this->config->getParameter('deploy-dir')) {
            if (!is_dir($deployDir)) {
                $console = Console::getInstance();
                $console->write("Deploy dir doesn't exists: ". $deployDir . "\n", ColorInterface::RED);
                exit();

            }
            $this->deploy($deployDir);
        }

        if ($this->config->getParameter('erase')) {
            $this->eraseFolders();
        }

        return null;
    }

    public function deploy($outDir)
    {
        $this->deployDir = rtrim($outDir, '/');

        if (!is_dir($outDir) || !is_writeable($outDir)) {
            throw new ErrorException ('Directory is not writable: ' . $outDir);
        }

        foreach ($this->outDirArray as $dir) {
            $dir = str_replace($this->outDir, $this->deployDir, $dir);
            @mkdir($dir);
        }

        foreach (glob($this->outDir . '/abstract/*.php') as $filename) {
            $deployFile = $this->deployDir . '/abstract/' . basename($filename);
            copy($filename, $deployFile);
        }

        @unlink($this->deployDir . '/_autoload_classmap.php');
        foreach (glob($this->outDir . '/*.php') as $filename) {
            $deployFile = $this->deployDir . '/' . basename($filename);

            if (!is_file($deployFile)) {
                copy($filename, $deployFile);
            }
        }

        foreach (glob($this->outDir . '/cond/abstract/*.php') as $filename) {
            $deployFile = $this->deployDir . '/cond/abstract/' . basename($filename);
            copy($filename, $deployFile);
        }

        foreach (glob($this->outDir . '/cond/*.php') as $filename) {
            $deployFile = $this->deployDir . '/cond/' . basename($filename);

            if (!is_file($deployFile)) {
                copy($filename, $deployFile);
            }
        }

        foreach (glob($this->outDir . '/collections/abstract/*.php') as $filename) {
            $deployFile = $this->deployDir . '/collections/abstract/' . basename($filename);
            copy($filename, $deployFile);
        }

        foreach (glob($this->outDir . '/collections/*.php') as $filename) {
            $deployFile = $this->deployDir . '/collections/' . basename($filename);

            if (!is_file($deployFile)) {
                copy($filename, $deployFile);
            }
        }

        foreach (glob($this->outDir . '/entities/abstract/*.php') as $filename) {
            $deployFile = $this->deployDir . '/entities/abstract/' . basename($filename);
            copy($filename, $deployFile);
        }

        foreach (glob($this->outDir . '/entities/*.php') as $filename) {
            $deployFile = $this->deployDir . '/entities/' . basename($filename);

            if (!is_file($deployFile)) {
                copy($filename, $deployFile);
            }
        }
    }

    /**
     * Удалить output модели
     */
    public function eraseFolders()
    {
        $outDirArray = $this->outDirArray;

        foreach ($outDirArray as $dir) {
            if (is_dir($dir)) {
                foreach (glob("{$dir}/*.php") as $filename) {
                    unlink($filename);
                }
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
}