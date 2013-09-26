<?php

/**
 * Copyright (c) 2013 Will Hattingh (https://github.com/Nitecon
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 * 
 * @author Will Hattingh <w.hattingh@nitecon.com>
 *
 * 
 */

namespace ZfcUserLdap\Service;
use ZfcUserLdap\Provider\Identity\LdapIdentityProvider;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LdapIdentityProviderServiceFactory implements FactoryInterface {

    public function createService(ServiceLocatorInterface $serviceLocator) {
        /* @var $userService \ZfcUser\Service\User */
        $userService = $serviceLocator->get('zfcuser_user_service');
        //$serviceLocator->get('zfcuser_auth_service');
        $config      = $serviceLocator->get('BjyAuthorize\Config');

        $provider = new LdapIdentityProvider($userService,$config);

        $provider->setDefaultRole($config['default_role']);

        return $provider;
    }

}