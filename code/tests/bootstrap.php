<?php

declare(strict_types=1);

use AssetGrabber\Tests\Helpers\FunctionalTestHelper;

require_once __DIR__ . '/../vendor/autoload.php';

$config = require './config/config.php';

ini_set('memory_limit', '4G');

$dependencies                       = $config['dependencies'];
$dependencies['services']['config'] = $config;


$container = new \Laminas\ServiceManager\ServiceManager($dependencies);
$container->setAllowOverride(true);
$config = $container->get('config');
$config['database']['schema'] = 'testing';
$container->setService('config', $config);
$container->setAllowOverride(false);

FunctionalTestHelper::setContainer($container);
FunctionalTestHelper::resetDatabase();


