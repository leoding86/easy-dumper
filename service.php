<?php
define('ROOT', str_replace('\\', '/', __DIR__));

require './vendor/autoload.php';
require './Autoloader.php';

Autoloader::getLoader();

$args = \Common\Helper::parseArgv($argv);

if (!isset($args['S'])) {
    echo 'No service has been specified' . PHP_EOL;
    exit;
}

$service = \Common\ServiceFactory::get($args['S'], $args);
$service->start();