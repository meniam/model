<?php

/**
 * Setup autoloading
 */
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    include_once __DIR__ . '/../vendor/autoload.php';
} else {
    // if composer autoloader is missing, explicitly add the ZF library path
    $file = __DIR__ . '/../vendor/' . 'zendframework/library/Zend/Loader/StandardAutoloader.php';

    if (is_file($file)) {
        require_once $file;

        $loader = new Zend\Loader\StandardAutoloader(
            array(
                 Zend\Loader\StandardAutoloader::LOAD_NS => array(
                     'Zend'     => __DIR__ . '/../vendor/zendframework/library/Zend',
                     'Model'     => __DIR__ . '/../Model',
                     'ModelTest' => __DIR__ . '/ModelTest',
                 ),
            ));
        $loader->register();
    }
}
