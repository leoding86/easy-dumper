<?php
define('ROOT', str_replace('\\', '/', __DIR__));
define('RUNTIME', ROOT . '/Runtime');
define('DB', ROOT . '/Db');
define('DUMPED', ROOT . '/Dumped');
define('TEMPLATE', ROOT . '/Template');

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