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

namespace ZfcUserLdap\ServiceFactory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfcUserLdap\Provider\Identity\ZfcRbacIdentityProvider;

class ZfcRbacIdentityProviderFactory implements FactoryInterface
{

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {

        $authService = $serviceLocator->get('zfcuser_auth_service');
        $config = $serviceLocator->get('ZfcUserLdap\Config');

        $provider = new ZfcRbacIdentityProvider($authService, $config);
        return $provider;
    }
}
