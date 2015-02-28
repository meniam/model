<?php
/**
 * Created by PhpStorm.
 * User: RuSPanzer
 * Date: 28.02.2015
 * Time: 12:34
 */

namespace Model\Generator;


use Model\Config\Config;
use Model\Stdlib\ArrayUtils;
use Zend\Console\ColorInterface;
use Zend\Console\Console;
use Zend\Console\RouteMatcher\DefaultRouteMatcher;

class Standalone
{

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
        $paramName = preg_replace("#<(dir|str|file)>#", $console->colorize("<\\1>", ColorInterface::WHITE), $paramName);
        $paramName = preg_replace("#(\\[\\-\\-.*?\\])#", $console->colorize("\\1", ColorInterface::WHITE), $paramName);

        $console->write($shiftStr . $paramName . "  " . $console->colorize($name, ColorInterface::LIGHT_WHITE) . PHP_EOL);

        $descriptionArray = array_map('trim', explode("\n", $description));
        foreach ($descriptionArray as $descriptionItem) {
            $console->write($shiftStr . str_repeat(" ", 24) . $descriptionItem . "\n");
        }

        return null;
    }


    public function showUsage()
    {
        $shiftLen = 16;

        $help = array(
            "Models by Eugene Myazin <github.com/meniam/models>.",
            ''
        );

        $this->showLine($help, ColorInterface::WHITE);

        $this->showLine("Usage# ./models --deploy-dir=<dir> \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("                --output-dir=<dir> \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--config=<file>] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--db-host=<str>] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--db-schema=<str>] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--db-user=<str>] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--db-password=<str>] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--force] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--verbose] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--erase] \\", ColorInterface::LIGHT_WHITE, $shiftLen);
        $this->showLine("               [--cache-dir=<dir>]", ColorInterface::LIGHT_WHITE, $shiftLen);

        $this->showLine("");
        $this->showParam("--deploy-dir=<dir>", "Directory for deployment models",
            "In which the replacement copied abstract classes," . PHP_EOL .
            "base classes are copied if there are not in folder." . PHP_EOL,
            $shiftLen);

        $this->showParam("--output-dir=<dir>", "The directory in which the generated model",
            "cleaned before each run generation" . PHP_EOL,
            8);

        $this->showParam("[--config=<file>]", "Path to JSON config file",
            "You can use your configuration file to generate models." . PHP_EOL.
            "If you do not specify this option, it will be used default config - models.json" . PHP_EOL,
            8);

        $this->showParam("[--db-host=<str>]", "Server address",
            "default: localhost" . PHP_EOL,
            8);

        $this->showParam("[--db-schema=<str>]", "Database name",
            "default: test" . PHP_EOL,
            8);

        $this->showParam("[--db-user=<str>]", "Database user",
            "default: root" . PHP_EOL,
            8);

        $this->showParam("[--db-password=<str>]", "Database password",
            "default: null" . PHP_EOL,
            8);

        $this->showParam("[--cache-dir=<str>]", "Cache dir",
            "use this parameter to enable generator cache," . PHP_EOL .
            "default cache disable" . PHP_EOL,
            8);

        $this->showParam("[--verbose]", "Show generator log",
            "Show generator log to console" . PHP_EOL,
            8);

        $this->showParam("[--force]", "God mode",
            "ignore all generating errors" . PHP_EOL.
            "ignore cache" . PHP_EOL,
            8);

        $this->showParam("[--erase]", "Clear output dir",
            "clear output dir after deployment models" . PHP_EOL,
            8);

        return null;
    }

    public function run($commandString = array())
    {
        if ($commandString) {
            if (!is_array($commandString)) {
                $commandString = 'models ' . $commandString;
            }
            $commandString = explode(' ', $commandString);
        } else {
            $commandString = $_SERVER['argv'];
        }

        if (count($commandString) < 2) {
            return $this->showUsage();
        }

        $params = new DefaultRouteMatcher("[--output-dir=] "
            . "[--deploy-dir=] "
            . "[--config=] "
            . "[--db-host=] "
            . "[--db-schema=]  "
            . "[--db-user=] "
            . "[--db-password=] "
            . "[--deploy=] "
            . "[--verbose] "
            . "[--help] "
            . "[--force] "
            . "[--erase] "
            . "[--cache-dir=] ");
        $argv   = $commandString;
        array_shift($argv);
        $consoleParams = $params->match($argv);

        $host     = isset($consoleParams['db-host']) ? $consoleParams['db-host'] : '127.0.0.1';
        $dbSchema = isset($consoleParams['db-schema']) ? $consoleParams['db-schema'] : 'test';
        $user     = isset($consoleParams['db-user']) ? $consoleParams['db-user'] : 'root';
        $password = isset($consoleParams['db-password']) ? $consoleParams['db-password'] : '';

        $dsn = "mysql:host=" . $host . ';'
            . 'dbname=' . $dbSchema . ';'
            . 'charset=utf8';

        $configParams = ArrayUtils::filterByOriginalArray($consoleParams, array(
            'output-dir',
            'deploy-dir',
            'config',
            'verbose',
            'erase',
            'force',
        ));
        $configParams = array_merge($configParams, array(
            'dsn' => $dsn,
            'user' => $user,
            'password' => $password
        ));

        $config = new Config($configParams);

        $generator = new Generator($config);
        $generator->run();
    }
}