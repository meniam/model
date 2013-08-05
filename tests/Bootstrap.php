<?php

defined('PROJECT_PATH') || define('PROJECT_PATH', dirname(__FILE__) . '/../');

// Папка с App
defined('ZENDLIB_PATH')  || define('ZENDLIB_PATH',   (getenv('ZENDLIB_PATH')   ?: PROJECT_PATH . '/vendor' ));

define('FIXTURES_PATH',   realpath(__DIR__ . '/_fixtures'));

include __DIR__ . '/_autoload.php';

$db = new Model\Db\Mysql('mysql:host=localhost;dbname=model_test;charset=UTF8', 'root', 'macbook');

/** @var Zend\Cache\Storage\Adapter\Filesystem $cache */
$cache = \Zend\Cache\StorageFactory::factory(array(
    'adapter' => array(
        'name' => 'filesystem',
        'options' => array(
            'cache_dir' => __DIR__ . '/cache'),
        'dir_level' => 3,
        'dir_permission' => 0777,
        'file_permission' => 0666,
        'no_atime' => true
    ),
));

$plugin = new \Zend\Cache\Storage\Plugin\Serializer();
$cache->addPlugin($plugin);

\Model\Cluster\Schema::setCacheAdapter($cache);
ModelTest\TestCase::setDb($db);
