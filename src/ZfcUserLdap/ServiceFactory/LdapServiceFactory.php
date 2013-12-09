<?php
/**
 * This file is part of the ZfcUserLdap Module (https://github.com/Enrise/ZfcUser-Ldap)
 *
 * Copyright (c) 2013 Rob Quist (https://github.com/RobQuistNL)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 */

namespace ZfcUserLdap\ServiceFactory;

use ZfcUserLdap\Service\LdapService;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LdapServiceFactory implements FactoryInterface {

    /**
     * @see \Zend\ServiceManager\FactoryInterface::createService()
     */
    public function createService(ServiceLocatorInterface $serviceLocator) {
        $config = $serviceLocator->get('Config');
        return new LdapService($config['ldap']);
    }

}