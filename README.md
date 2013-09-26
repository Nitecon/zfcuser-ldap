ZfcUserLdap
================

Zend Framework ZfcUser Extension to provide LDAP Authentication

## Features
- Provides an adapter chain for LDAP authentication.
- Allows login by username & email address
- Provides automatic failover between configured LDAP Servers
- Provides an identity provider for BjyAuthorize

## WIP
The current items that I'm working on is a new storage method for ZfcUserLdap so that it can cache the
user object more efficiently.  Ldap and especially openldap is generally extremely fast however there 
are times where it may lag so the idea is to cache the user object while the user is logged in and destroy
the cache on session destroy.

## Setup

The following steps are necessary to get this module working

  1. Run `php composer.phar require nitecon/zfcuser-ldap:dev-master`
  2. Add `ZfcUserLdap` to the enabled modules list (Requires ZfcUser to be activated also)
  3. Add Zend Framework LDAP configuration to your autoload with key 'ldap' based on:
     http://framework.zend.com/manual/2.1/en/modules/zend.ldap.introduction.html

     An example of the configuration is shown below for configs/autoload/global.php
     *Please make sure you do not include passwords in this file, I've included it
     for illustration purposes only*
    <pre class="brush:php">
    array(
    'ldap' => array(
        'server1' => array(
            'host'              => 's0.foo.net',
            'username'          => 'CN=user1,DC=foo,DC=net',
            'password'          => 'pass1',
            'bindRequiresDn'    => true,
            'accountDomainName' => 'foo.net',
            'baseDn'            => 'OU=Sales,DC=foo,DC=net',
        ),
        'server2' => array(
            'host'              => 's0.foo2.net',
            'username'          => 'CN=user1,DC=foo,DC=net',
            'password'          => 'pass1',
            'bindRequiresDn'    => true,
            'accountDomainName' => 'foo.net',
            'baseDn'            => 'OU=Sales,DC=foo,DC=net',
        ),
    )
    ),
      </pre>

## BjyAuthorize Setup

To enable BjyAuthorize on the ZfcUserLdap module all you will need to do is add the
following to your Application's module.config.php

<pre class="brush:php">
return array(
 // Your other stuff
'bjyauthorize' => array(
        // Using the authentication identity provider, which basically reads the roles from the auth service's identity
        'identity_provider' => 'ZfcUserLdap\Provider\Identity\LdapIdentityProvider',
        'ldap_role_key' => 'objectclass',
        'role_providers' => array(
            /* here, 'guest' and 'user are defined as top-level roles, with
             * 'posixAccount' inheriting from user
             */
            'BjyAuthorize\Provider\Role\Config' => array(
                'guest' => array(),
                'user' => array('children' => array(
                        'person' => array(),
                        'posixAccount' => array(),
                    )),
            ),
        ),
        'guards' => array(
            'BjyAuthorize\Guard\Route' => array(
                array('route' => 'zfcuser', 'roles' => array('posixAccount')),
                array('route' => 'zfcuser/logout', 'roles' => array('posixAccount')),
                array('route' => 'zfcuser/login', 'roles' => array('guest')),
                array('route' => 'zfcuser/register', 'roles' => array('guest')),
                // Below is the default index action used by the ZendSkeletonApplication
                array('route' => 'home', 'roles' => array('guest', 'person')),
            ),
        )
    ),
    // More of your other stuff
    );
</pre>

Majority of the items should be familiar to you based on BjyAuthorize documentation however
I will go over the critical parts for ZfcUserLdap to function correctly.  

The first important
section is `'identity_provider' => 'ZfcUserLdap\Provider\Identity\LdapIdentityProvider',`.

The next part is: `'ldap_role_key' => 'objectclass',`
This maps directly to the ldap object that is returned on search.  If you would like to see the
actual object based on your ldap server please take a look at `data/logs/ldap.log`
I've chosen `objectclass` as the key to use for roles but you can use any top level key you desire
some ldap teams provide managed role keys like `mycompanyrole` with sub keys of the actual roles the
user currently has, with that being the case set your `ldap_role_key` to `mycompanyrole`

The final part is the `role_providers` key for BjyAuthorize, because ldap users are dynamic and not
all users always have the same roles we define the baseline with the `BjyAuthorize\Provider\Role\Config`
role provider.  The guest and user roles are required as `guest` indicates not logged in user, and `user`
indicates a user that has successfully logged in, that way you have the ability to give everyone on ldap
access to a specific set and restrict access to additional sections with the roles provided.

Now some more talks on the baseline that is set within the `user` children.  The children keys should 
*always* map to existing array keys within the `ldap_role_key` element that you have selected.  As 
`objectclass` is a sane ldap default the same with `person` and `posixAccount` I've decided to use them
as baselines.  Keep in mind that you can add any roles you like in this section and even have them inherit
if you so desire, the only stipulation is when a user logs in the IdentityProvider will check to see the base
roles that are selected and will automatically unset any additional roles that the user has under the key.
The reason for this is because BjyAuthorize will freak out if the user has a role that it does not know about.

Enjoy and if you have issues please let me know.

## Additional Information

Please note that based on the above configuration example it shows 2 servers being
used which will allow automatic failover if one server is down.  This is NOT required.
You are only required to specify one server, so the 'server2' array can be removed.

The application will currently also log all LDAP related queries to data/logs/ldap.log
If you do not have a data directory please create one the application will automatically
try to create it however if it can't create the folder then it will error out.  This will
be changed in the future to allow the user to specify log location and also disable logging
completely.

## Application Configuration
Please make sure to enable both ZfcUser and ZfcUserLdap in your application.config.php as
shown below

<pre class="brush:php">
  array(
    'ZfcUser',
    'ZfcUserLdap',
    /* It's important to load ZfcUser before ZfcUserLdap as ZfcUserLdap is an addon to ZfcUser */
  );
</pre>

## ZfcUser Configuration

For the initial release please make sure to set the following settings in your
zfcuser configuration:
<pre class="brush:php">
    array(
        'enable_registration' => false,
        'enable_username' => true,
        'auth_adapters' => array( 100 => 'ZfcUserLdap\Authentication\Adapter\Ldap' ),
        'auth_identity_fields' => array( 'username','email' ),
    ),
</pre>

## Final notes

In the above configuration auth_identity_fields can be left as email only if you like,
however it's recommended to allow ldap users to be able to log in with their ldap uid.
enable_registration should however be turned off at this point as it will cause issues
when the user tries to sign up and it can't create the entity within LDAP.

There are some more error handling that needs to be done in the module, as well as
the ability to make modifications as per the zfcuser module abilities.  However this
has currently been disabled and you will not be able to *change* passwords.

Enjoy and if you find bugs or issues please add pull requests for the module.
