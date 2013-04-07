DBSessionStorage
================

Zend Framework ZfcUser Extension to provide LDAP Authentication

## Features
- Provides an adapter chain for LDAP authentication.
- Allows login by username & email address
- Provides automatic failover between configured LDAP Servers

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

## Additional Information

Please note that based on the above configuration example it shows 2 servers being
used which will allow automatic failover if one server is down.  This is NOT required.
You are only required to specify one server, so the 'server2' array can be removed.

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
