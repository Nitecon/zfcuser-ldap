<?php

/**
 * This file is part of the ZfcUserLdap Module (https://github.com/Nitecon/zfcuser-ldap.git)
 *
 * Copyright (c) 2013 Will Hattingh (https://github.com/Nitecon/zfcuser-ldap)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 */

namespace ZfcUserLdap\ServiceFactory;

use ZfcUserLdap\Service\LdapInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LdapServiceFactory implements FactoryInterface {

    public function createService(ServiceLocatorInterface $serviceLocator) {
        $config = $serviceLocator->get('Config');
        return new LdapInterface($config['ldap']);
    }

}