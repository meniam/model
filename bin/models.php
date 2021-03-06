#!/usr/bin/env php

<?php

foreach (array(__DIR__ . './../vendor/autoload.php') as $file) {
    if (file_exists($file)) {
        define('MODELS_COMPOSER_INSTALL', $file);
        break;
    }
}

if (!defined('MODELS_COMPOSER_INSTALL')) {
    die(
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'wget http://getcomposer.org/composer.phar' . PHP_EOL .
        'php composer.phar install' . PHP_EOL
    );
}

require MODELS_COMPOSER_INSTALL;

(new \Model\Generator\Standalone())->run();
