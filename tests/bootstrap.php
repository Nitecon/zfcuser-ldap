<?php



// Composer autoloading
if (file_exists('./vendor/autoload.php')) {
    $loader = include './vendor/autoload.php';
}
$loader->add('ZfcUserTest', "./tests/ZfcUserLdapTest/src/ZfcUserLdapTest");
Zend\Mvc\Application::init(require './tests/config/application.config.php');
