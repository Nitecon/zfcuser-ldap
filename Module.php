<?php

/**
 * This file is part of the ZfcUserLdap Module (https://github.com/Enrise/ZfcUser-Ldap)
 *
 * Copyright (c) 2013 Rob Quist (https://github.com/Enrise)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 */

namespace ZfcUserLdap;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module {

    /**
     * Bootstrapper for module
     * @param MvcEvent $e
     */
    public function onBootstrap(MvcEvent $e) 
    {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    /**
     * Get the autoloader configuration
     * @return array $autoloaderconfig
     */
    public function getAutoloaderConfig() 
    {
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

    /**
     * get Service config
     * @return $serviceConfig array|null|\ZfcUserLdap\Options\ModuleOptions|\ZfcUserLdap\Mapper\User|\ZfcUser\Mapper\User
     */
    public function getServiceConfig() 
    {
        return array(
            'invokables' => array(
                'ZfcUserLdap\Authentication\Adapter\Ldap' => 'ZfcUserLdap\Authentication\Adapter\Ldap',
            ),
            'factories' => array(
                'zfcuser_ldap_service' => 'ZfcUserLdap\ServiceFactory\Ldap',
                'ldap_interface' => 'ZfcUserLdap\ServiceFactory\LdapServiceFactory',
                'zfcuser_module_options' => function ($sm) {
                    $config = $sm->get('Configuration');
                    return new Options\ModuleOptions(isset($config['zfcuser']) ? $config['zfcuser'] : array());
                },
                'zfcuser_user_mapper' => function ($sm) {
                    $config = $sm->get('Config');
                    return new \ZfcUserLdap\Mapper\User(
                            $sm->get('ldap_interface'), $sm->get('zfcuser_module_options'), $config['ldap']
                    );
                },
                'zfcuser_user_db_mapper' => function ($sm) {
                    $options = $sm->get('zfcuser_module_options');
                    $mapper = new \ZfcUser\Mapper\User();
                    $mapper->setDbAdapter($sm->get('zfcuser_zend_db_adapter'));
                    $entityClass = $options->getUserEntityClass();
                    $mapper->setEntityPrototype(new $entityClass);
                    $mapper->setHydrator(new \ZfcUser\Mapper\UserHydrator());
                    return $mapper;
                },
            ),
        );
    }

    /**
     * Include the module config
     */
    public function getConfig() 
    {
        return include __DIR__ . '/config/module.config.php';
    }

}