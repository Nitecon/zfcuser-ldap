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
return
        array(
            'ldap' => array(
                'server1' => array(
                    'host' => 's0.foo.net',
                    'username' => 'CN=user1,DC=foo,DC=net',
                    'password' => 'pass1',
                    'bindRequiresDn' => true,
                    'accountDomainName' => 'foo.net',
                    'baseDn' => 'OU=Sales,DC=foo,DC=net',
                ),
                'server2' => array(
                    'host' => 's0.foo2.net',
                    'username' => 'CN=user1,DC=foo,DC=net',
                    'password' => 'pass1',
                    'bindRequiresDn' => true,
                    'accountDomainName' => 'foo.net',
                    'baseDn' => 'OU=Sales,DC=foo,DC=net',
                ),
            )
);
