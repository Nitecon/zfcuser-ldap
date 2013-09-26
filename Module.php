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
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getServiceConfig() {
        return array(
            'invokables' => array(
                'ZfcUserLdap\Authentication\Adapter\Ldap' => 'ZfcUserLdap\Authentication\Adapter\Ldap',
            ),
            'factories' => array(
                'ZfcUserLdap\Provider\Identity\LdapIdentityProvider' => 'ZfcUserLdap\Service\LdapIdentityProviderServiceFactory',
                'zfcuser_ldap_service' => 'ZfcUserLdap\ServiceFactory\Ldap',
                'ldap_interface' => 'ZfcUserLdap\ServiceFactory\LdapServiceFactory',
                'zfcuser_module_options' => function ($sm) {
                    $config = $sm->get('Configuration');
                    return new Options\ModuleOptions(isset($config['zfcuser']) ? $config['zfcuser'] : array());
                },
                'zfcuser_user_mapper' => function ($sm) {
                    return new \ZfcUserLdap\Mapper\User(
                            $sm->get('ldap_interface'), $sm->get('zfcuser_module_options')
                    );
                },
            ),
        );
    }

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

}
