ZfcUserLdap
================

[![Build Status](https://travis-ci.org/Nitecon/zfcuser-ldap.png?branch=master)](https://travis-ci.org/Nitecon/zfcuser-ldap) [![Latest Stable Version](https://poser.pugx.org/nitecon/zfcuser-ldap/v/stable.png)](https://packagist.org/packages/nitecon/zfcuser-ldap) [![Coverage Status](https://coveralls.io/repos/Nitecon/zfcuser-ldap/badge.png?branch=master)](https://coveralls.io/r/Nitecon/zfcuser-ldap?branch=master)

Zend Framework ZfcUser Extension to provide LDAP Authentication



## Features
- Provides an adapter chain for LDAP authentication.
- Allows login by username & email address
- Provides automatic failover between configured LDAP Servers
- Provides an identity provider for BjyAuthorize
 
## TODO / In Progress
- Add ability to register a user in ldap
- Allow password resets in ldap
- Provide an identity provider for ZfcRbac

## Rebuild notice
If you do not feel like testing the WIP, please make sure to use the current
release version: 1.1.0 as mentioned below in the setup for composer.  Although
I will try to keep the master branch as stable as possible it's always
possible that I can break your stuff.

Due to ZfcRbac requiring `getRoles` in the user entity I will just adapt the 
same thing for BjyAuthorize.  On a side note the roles may not be inserted 
into the database as they have the possibility to change and storing them 
in session seems to make more sense.

## WIP
Currently the master branch is functional but without documentation,
I will try my best to add documentation for the master branch and add
a new release in the following week or so, however work life is not always
forgiving but I will get it in here shortly.

The entire module has been re-built from scratch, I have tried my best 
to keep backwards compatability, however to get some of the fixes in I 
did have to break the current release version.

What breaks?  You now need to have a column in your zfcuser user table
`roles`  I currently have this set to a blob right now as my ldap user
does not fit within text or longtext.  Beyond that there is a few new things.

Under the module (in your vendor directory) you will find a new config file 
`zfcuserldap.global.php.dist`  You need to either copy & rename this file to
`config/autoload/zfcuser.global.php` or add it to your own global file.
The file includes new configuration settings for the module, it also includes
a new feature called: `auto_insertion`.  If auto insertion is enabled (default)
then when an authentication is triggered *AND* successful the user will be added
to your zfcuser user table automatically.  If you decide to disable this then
you can still use the module however you will have to create a user in your db
table first.  This gives additional restrictions that weren't previously possible
and only available through bjyauthorize roles.

Another fairly big change is that the module now features a proper chain auth
adapter, and adjustable entity.  Where before you had to set: 
<pre class="brush:php">'auth_adapters' => array( 
    100 => 'ZfcUserLdap\Authentication\Adapter\Ldap' ),
</pre>
you can now more efficiently have something like: 
<pre class="brush:php">'auth_adapters' => array( 
    110 => 'ZfcUserLdap\Authentication\Adapter\LdapAuth', 
    100 => 'ZfcUser\Authentication\Adapter\Db' ),
</pre>
Please keep in mind that the password is not stored in the database as I'm partial
to allowing that to happen so if you disable the zfcuserldap your user still exists
however you would need to reset the password.

Due to it now running on a db with insertions it also means that you have to actually
specify the user entity to be used as mentioned 2 paragraphs ago!  By default you
can set the following for your zfcuser configuration: 
<pre class="brush:php">
    'user_entity_class' => 'ZfcUserLdap\Entity\User',
</pre>

You could always override the entity but please keep in mind that you will need
to include the rawLdapObj property with it's getter/setter in your entity as well as the associated table column `raw_ldap_obj`

BjyAuthorize is not affected by the change and you should be able to use the
identity provider as you had it before.

## Setup

The following steps are necessary to get this module working

  1. Run `php composer.phar require nitecon/zfcuser-ldap:1.1.0`
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
