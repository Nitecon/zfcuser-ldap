<?php

/**
 * This file is part of the ZfcUserLdap Module (https://github.com/Nitecon/zfcuser-ldap.git)
 *
 * Copyright (c) 2013 Will Hattingh (https://github.com/Nitecon/zfcuser-ldap)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 */
return array(
    'service_manager' => array(
        'invokables' => array(
            'ZfcUserLdap\Adapter\Ldap' => 'ZfcUserLdap\Adapter\Ldap',
            'ZfcUserLdap\Authentication\Adapter\LdapAuth' => 'ZfcUserLdap\Authentication\Adapter\LdapAuth',
        ),
        'aliases' => array(
        ),
        'factories' => array(
            'ZfcUserLdap\Config' => 'ZfcUserLdap\ServiceFactory\ZfcUserLdapConfigFactory',
            'ZfcUserLdap\LdapAdapter' => 'ZfcUserLdap\ServiceFactory\LdapAdapterFactory',
            'ZfcUserLdap\LdapConfig' => 'ZfcUserLdap\ServiceFactory\LdapConfigFactory',
            'ZfcUserLdap\Logger' => 'ZfcUserLdap\ServiceFactory\LoggerAdapterFactory',
            'ZfcUserLdap\Mapper' => 'ZfcUserLdap\ServiceFactory\UserMapperFactory',
            'ZfcUserLdap\Provider\Identity\LdapIdentityProvider' => 'ZfcUserLdap\Service\LdapIdentityProviderFactory',
            'ZfcUserLdap\ZfcRbacIdentityProvider' => 'ZfcUserLdap\ServiceFactory\ZfcRbacIdentityProviderFactory',
        )
    ),
);
