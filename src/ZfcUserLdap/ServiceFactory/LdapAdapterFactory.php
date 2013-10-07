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

class LdapAdapterFactory implements FactoryInterface
{

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('ZfcUserLdap\LdapConfig');
        $logger = $serviceLocator->get('ZfcUserLdap\Logger');
        $zulconfig = $serviceLocator->get('ZfcUserLdap\Config');

        return new \ZfcUserLdap\Adapter\Ldap($config, $logger, $zulconfig['logging']['log_enabled']);
    }
}
