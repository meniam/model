<?php
error_reporting( E_ALL | E_STRICT );

/*
 * Determine the root, library, and tests directories of the framework
 * distribution.
 */
$zfRoot        = realpath(dirname(__DIR__));
$zfCoreLibrary = "$zfRoot/vendor/zendframework/library";
$zfCoreTests   = "$zfRoot/tests";

/*
 * Prepend the Zend Framework library/ and tests/ directories to the
 * include_path. This allows the tests to run out of the box and helps prevent
 * loading other copies of the framework code and tests that would supersede
 * this copy.
 */
$path = array(
    $zfCoreLibrary,
    $zfCoreTests,
    get_include_path(),
);
set_include_path(implode(PATH_SEPARATOR, $path));

/**
 * Setup autoloading
 */
include __DIR__ . '/_autoload.php';
