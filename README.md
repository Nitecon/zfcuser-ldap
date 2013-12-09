ZfcUserLdap
================

Zend Framework ZfcUser Extension to provide LDAP Authentication using the 
ADLDAP PHP Class (http://adldap.sourceforge.net/)

Module structure based on ZfcUserLdap by Nitecon (https://github.com/Nitecon/zfcuser-ldap)

## Features
- Provides an adapter chain for LDAP authentication.
- Allows login by username

## Setup

The following steps are necessary to get this module working
  
  1. Make sure ZfcUser is working correctly
  2. Clone https://github.com/Enrise/ZfcUser-Ldap into the vendor dir (as ZfcUserLdap) or use Composer
  3. Add `ZfcUserLdap` in the modules array in application.config.php 
  4. Add Zend Framework LDAP configuration to your autoload

     An example of the configuration is shown below for configs/autoload/global.php
     *Please make sure you do not include passwords in this file, I've included it
     for illustration purposes only*
    <pre class="brush:php">
    array(
    'ldap' => array(
        'domain_controllers' => array (
            'dc01.example.com',
        ),
        'account_suffix'       => '@example.com',
        'admin_password'       => '', //Not neccesary, perhaps for future functions
        'admin_username'       => '', //Not neccesary
        'base_dn'              => 'DC=example,DC=com',
        'default_email_domain' => 'example.com', //Will be appended after username (inc. @-sign) as e-mail for database
    ),
      </pre>
      
      
## Additional Information

This application does not log anything. It simply spurts out exceptions if 
something went wrong.

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
        'auth_identity_fields' => array( 'username' ),
    ),
</pre>

## Final notes

This whole module isn't done. It works, but its far from finalized.
Feel free to add some more functions.

Enjoy and if you find bugs or issues please add pull requests for the module.
