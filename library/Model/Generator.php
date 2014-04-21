<?php

namespace Model;

use Model\Cluster\Schema;
use Model\Db\Mysql;
use Model\Exception\ErrorException;
use Model\Generator\Part\AbstractPart;
use Model\Db\Mysql as DbAdapter;

use Model\Cluster\Schema\Table;
use Model\Generator\Part\Entity;
use Zend\Console\ColorInterface;
use Zend\Console\Console;
use Zend\Console\RouteMatcher\DefaultRouteMatcher;

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

    private function setOutDir($outDir)
    {
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

    private $config = array();

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
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
        $options = $aliasName ? array('alias' => $aliasName, 'config' => $this->getConfig()) : array('config' => $this->getConfig());

        new $partClass($table, $this->getCluster(), $outputFile, $options);
    }

    public function showUsage()
    {
        $shiftLen = 16;

        $help = array(
            "Models 1.0.0 by Eugene Myazin <github.com/meniam/models>.",
            ''
        );

        $this->showLine($help, ColorInterface::WHITE);

        $this->showLine("Usage# ./models --deploy-dir=<dir> \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("                --output-dir=<dir> \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--db-host=<str>] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--db-schema=<str>] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--db-user=<str>] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--db-password=<str>] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--force] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--verbose] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--cache-dir=<dir>]", ColorInterface::LIGHT_WHITE, $shiftLen);

        $this->showLine("");
        $this->showParam("--deploy-dir=<dir>", "Директория для выгрузки готовых моделей",
                                         "в которую с заменой копируются абстрактные классы," . PHP_EOL .
                                         "базовые классы копируются если отсутствует в папке." . PHP_EOL,
            $shiftLen);

        $this->showParam("--output-dir=<dir>", "Директория в которую генерируются модели",
            "очищается перед каждым запуском генерации" . PHP_EOL,
            8);

        $this->showParam("[--db-host=<str>]", "Адрес Mysql сервера",
            "по умолчанию: localhost" . PHP_EOL,
            8);

        $this->showParam("[--db-schema=<str>]", "Имя базы данных",
            "по умолчанию: test" . PHP_EOL,
            8);

        $this->showParam("[--db-user=<str>]", "Пользователь MySql",
            "по умолчанию: root" . PHP_EOL,
            8);

        $this->showParam("[--db-password=<str>]", "Пароль MySql",
            "по умолчанию: пустой" . PHP_EOL,
            8);

        $this->showParam("[--cache-dir=<str>]", "Директория для кеша",
            "если указана директория кеш включается," . PHP_EOL .
            "в обычном режиме кеша нет" . PHP_EOL,

            8);
        $this->showParam("[--verbose]", "Вывод действий на экран",
            "показывает что происходит в режиме реального времени" . PHP_EOL,
            8);

        $this->showParam("[--force]", "Режим бога",
            "в этом режиме скрипт игнорирует ошибки, все что может..." . PHP_EOL.
            "кеш игнорируется" . PHP_EOL,
            8);


        return null;
    }

    /**
     * @param array $lines
     * @param null  $color
     * @param int   $shift
     *
     * @return null
     */
    public function showLine($lines = array(), $color = null, $shift = 0)
    {
        $console = Console::getInstance();
        $shiftStr = str_repeat(" ", $shift);

        if (!is_array($lines)) {
            $descriptionArray = explode("\n", $lines);
            foreach ($descriptionArray as $descriptionItem) {
                $console->write($shiftStr . $descriptionItem . "\n", $color);
            }
        } else {
            foreach ($lines as $line) {
                $console->write($shiftStr . $line . PHP_EOL, $color);
            }
        }

        return null;
    }

    /**
     * @param $param
     * @param $name
     * @param $description
     *
     * @return null
     */
    public function showParam($param, $name, $description)
    {
        $console = Console::getInstance();

        $shiftStr = str_repeat(" ", 8);

        $paramName = str_pad($param, 22, " ", STR_PAD_LEFT);
        $paramName = $console->colorize($paramName, ColorInterface::LIGHT_WHITE);
        $paramName = preg_replace("#<(dir|str)>#", $console->colorize("<\\1>", ColorInterface::WHITE), $paramName);
        $paramName = preg_replace("#(\\[\\-\\-.*?\\])#", $console->colorize("\\1", ColorInterface::WHITE), $paramName);

        $console->write($shiftStr . $paramName . "  " . $console->colorize($name, ColorInterface::LIGHT_WHITE) . PHP_EOL);

        $descriptionArray = array_map('trim', explode("\n", $description));
        foreach ($descriptionArray as $descriptionItem) {
            $console->write($shiftStr . str_repeat(" ", 24) . $descriptionItem . "\n");
        }

        return null;
    }

    public function initLog($isVerbose, $logfile)
    {

    }


    /**
     * @throws \Exception
     */
    public function run($commandString = array())
    {
        if ($commandString) {
            if (!is_array($commandString)) {
                $commandString = 'models ' . $commandString;
            }
            $commandString = explode(' ', $commandString);
        } else {
            $commandString = $GLOBALS['argv'];
        }

        if (count($commandString) < 2) {
            return $this->showUsage();
        }

        $params = new DefaultRouteMatcher("[--output-dir=] "
                                        . "[--deploy-dir=] "
                                        . "[--db-host=] "
                                        . "[--db-schema=]  "
                                        . "[--db-user=] "
                                        . "[--db-password=] "
                                        . "[--deploy=] "
                                        . "[--verbose] "
                                        . "[--help] "
                                        . "[--force] "
                                        . "[--cache-dir=] ");
        $argv   = $commandString;
        array_shift($argv);
        $consoleParams = $params->match($argv);

        if (!isset($consoleParams['output-dir'])) {
            $console = Console::getInstance();
            $console->write("Unknown output dir. Use ./models --help\n", ColorInterface::RED);

            $this->showUsage();
            exit();
        }

        if (!is_dir($consoleParams['output-dir']) || !is_writeable($consoleParams['output-dir'])) {
            $console = Console::getInstance();
            $console->write("Unknown output dir not exists or not writable. Use ./models --help\n", ColorInterface::RED);
            exit();
        }

        $this->setOutDir($consoleParams['output-dir']);

        // Read Db Configuration

        $host     = isset($consoleParams['db-host']) ? $consoleParams['db-host'] : '127.0.0.1';
        $dbSchema = isset($consoleParams['db-schema']) ? $consoleParams['db-schema'] : 'test';
        $user     = isset($consoleParams['db-user']) ? $consoleParams['db-user'] : 'root';
        $password = isset($consoleParams['db-password']) ? $consoleParams['db-password'] : '';

        $dsn = "mysql:host=" . $host . ';'
               . 'dbname=' . $dbSchema . ';'
               . 'charset=utf8';
        $db  = new Mysql($dsn, $user, $password);

        $this->cluster = new Cluster();
        $this->cluster->addSchema((new Schema($dbSchema, $db))->init());

        $config = json_decode(file_get_contents(__DIR__ . '/models.json'), true);
        $this->setConfig($config);

        // Register plugins
        if (isset($config['plugins'])) {
            foreach ($config['plugins'] as $partType => $pluginArray)
                if (isset($pluginArray['list'])) {
                    foreach ($pluginArray['list'] as $condPluginArray) {
                        if ($pluginName = (isset($condPluginArray['name']) ? $condPluginArray['name'] : null)) {
                            $partTypeAsCamelCalse = implode('', array_map('ucfirst', explode('_', $partType)));

                            $condPluginClassName = '\\Model\\Generator\\Part\\Plugin\\' . $partTypeAsCamelCalse . '\\' . $pluginName;
                            $partConst           = constant('\\Model\\Generator\\Part\\AbstractPart::PART_' . strtoupper($partType));

                            AbstractPart::addPlugin(new $condPluginClassName(), $partConst);
                        }
                    }
                }
        }

        // Fields prepare

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
            $this->buildPart($table, 'collection');
            $this->buildPart($table, 'front_collection');
            $this->buildPart($table, 'entity');
            $this->buildPart($table, 'front_entity');
            $this->buildPart($table, 'model');
            $this->buildPart($table, 'front_model');

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

        if (isset($consoleParams['deploy-dir'])) {
            if (!is_dir($consoleParams['deploy-dir'])) {
                echo "Deploy dir doesn't exists: ". $consoleParams['deploy-dir'] . "\n";
                exit();

            }
            $this->deploy($consoleParams['deploy-dir']);
        }

        return null;
    }

    public function deploy($outDir)
    {
        $this->_deployDir = rtrim($outDir, '/');

        if (!is_dir($outDir) || !is_writeable($outDir)) {
            throw new ErrorException ('Directory is not writable: ' . $outDir);
        }

        foreach ($this->outDirArray as $dir) {
            $dir = str_replace($this->_outDir, $this->_deployDir, $dir);
            @mkdir($dir);
        }

        foreach (glob($this->_outDir . '/abstract/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/abstract/' . basename($filename);
            copy($filename, $deployFile);
        }

        @unlink($this->_deployDir . '/_autoload_classmap.php');
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
}