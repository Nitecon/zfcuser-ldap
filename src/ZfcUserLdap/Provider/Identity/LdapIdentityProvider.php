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
namespace ZfcUserLdap\Provider\Identity;
use BjyAuthorize\Exception\InvalidRoleException;
use Zend\Permissions\Acl\Role\RoleInterface;
use Zend\Session\Container;
class LdapIdentityProvider implements \BjyAuthorize\Provider\Identity\ProviderInterface{
    /**
     * @var User
     */
    protected $userService;

    /**
     * @var string|\Zend\Permissions\Acl\Role\RoleInterface
     */
    protected $defaultRole;
    
    protected $config;
    /**
     * @param \ZfcUser\Service\User    $userService
     */
    public function __construct($userService,$config)
    {
        $this->userService = $userService;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentityRoles()
    {
        $authService = $this->userService;
        $definedRoles = $this->config['role_providers']['BjyAuthorize\Provider\Role\Config']['user']['children'];
        $roleKey = $this->config['ldap_role_key'];
        
        
        if (! $authService->getAuthService()->hasIdentity()) {
            return array($this->getDefaultRole());
        }
        $session = new Container('ZfcUserLdap');
        if (!$session->offsetExists('ldapObj')){
            return array($this->getDefaultRole());
        }
        
        $user = $session->offsetGet('ldapObj');
        $roles     = array('user');
        foreach ($user[$roleKey] as $role) {
            if (isset($definedRoles[$role]))
                $roles[] = $role;
        }
        return $roles;
    }

    /**
     * @return string|\Zend\Permissions\Acl\Role\RoleInterface
     */
    public function getDefaultRole()
    {
        return $this->defaultRole;
    }

    /**
     * @param string|\Zend\Permissions\Acl\Role\RoleInterface $defaultRole
     *
     * @throws \BjyAuthorize\Exception\InvalidRoleException
     */
    public function setDefaultRole($defaultRole)
    {
        if (! ($defaultRole instanceof RoleInterface || is_string($defaultRole))) {
            throw InvalidRoleException::invalidRoleInstance($defaultRole);
        }

        $this->defaultRole = $defaultRole;
    }
}