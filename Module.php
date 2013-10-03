<?php

/**
 * This file is part of the ZfcUserLdap Module (https://github.com/Nitecon/zfcuser-ldap.git)
 *
 * Copyright (c) 2013 Will Hattingh (https://github.com/Nitecon/zfcuser-ldap)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 */

namespace ZfcUserLdap;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module {

    public function onBootstrap(MvcEvent $e) {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            
        );
    }

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

}
