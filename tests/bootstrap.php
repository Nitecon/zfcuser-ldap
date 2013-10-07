<?php

define('REQUEST_MICROTIME', microtime(true));
$basePath = dirname(__DIR__);
defined('BASE_PATH') || define('BASE_PATH', $basePath);
chdir($basePath);

// Composer autoloading
if (file_exists($basePath . '/vendor/autoload.php')) {
    $loader = include $basePath . '/vendor/autoload.php';
}
$loader->add('ZfcUserTest', "$basePath/tests/ZfcUserLdapTest/src/ZfcUserLdapTest");
Zend\Mvc\Application::init(require $basePath . '/tests/config/application.config.php');
