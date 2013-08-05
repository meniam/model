<?php

defined('PROJECT_PATH') || define('PROJECT_PATH', realpath(__DIR__ . '/../'));
defined('GENERATE_OUTPUT') || define('GENERATE_OUTPUT', '/tmp/generate');

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Setup autoloading
 */
require_once 'Zend/Loader/StandardAutoloader.php';
$loader = new \Zend\Loader\StandardAutoloader(
    array(
        'autoregister_zf' => true,
         Zend\Loader\StandardAutoloader::LOAD_NS => array(
             'App'      => '/Users/meniam/Sites/vendor/app/v2/App',
             'Model'     => __DIR__ . '/../Model',
             'ModelTest' => __DIR__ . '/ModelTest',
         ),
    ));
$loader->register();

if (is_file(GENERATE_OUTPUT . '/_autoload_classmap.php')) {
    $a = require(GENERATE_OUTPUT . '/_autoload_classmap.php');
    $loader = new \Zend\Loader\ClassMapAutoloader(array($a));
    $loader->register();
}
