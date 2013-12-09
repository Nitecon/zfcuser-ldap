<?php
/**
 * This file is part of the ZfcUserLdap Module (https://github.com/Enrise/ZfcUser-Ldap)
 *
 * Copyright (c) 2013 Rob Quist (https://github.com/RobQuistNL)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 */
namespace ZfcUserLdap\Service;

use Zend\Log\Logger;
use Zend\Log\Writer\Stream as LogWriter;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\Ldap as AuthAdapter;
use Zend\Ldap\Exception\LdapException;
use adLDAP\adLDAP;

class LdapService {

    /**
     * @var array config
     */
    private $config;
    
    /**
     * adLDAP handle
     * @var adldap adLDAP\adLDAP()
     */
    protected $adldap;

    public function __construct($config) 
    {
            $this->config = $config;
        $this->bind();
    }

    /**
     * Bind $this->adldap to a valid LDAP handle
     */
    public function bind() 
    {
        $this->adldap = new adLDAP($this->config);
    }

    /**
     * 
     * @param string $username
     * @param string $password
     * @return User information if success, false if not. array|boolean
     */
    function authenticate($username, $password) 
    {
        $auth = $this->adldap->authenticate($username, $password);
        
        if ($auth){
            return $this->adldap->user()->info($username);
        } 
        return false;
    }

}