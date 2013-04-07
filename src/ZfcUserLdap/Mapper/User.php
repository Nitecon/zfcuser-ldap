<?php
/**
 * This file is part of the ZfcUserLdap Module (https://github.com/Nitecon/zfcuser-ldap.git)
 *
 * Copyright (c) 2013 Will Hattingh (https://github.com/Nitecon/zfcuser-ldap)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 */
namespace ZfcUserLdap\Mapper;


use ZfcUser\Mapper\User as ZfcUserMapper;
use ZfcUserLdap\Options\ModuleOptions;
use ZfcUserLdap\Service\LdapInterface;
use Zend\Stdlib\Hydrator\HydratorInterface;


class User extends ZfcUserMapper
{
    /** @var \ZfcUserLdap\Service\LdapInterface */
    protected $ldap;
    /**
     * @var \ZfcUserLdap\Options\ModuleOptions
     */
    protected $options;

    public function __construct(LdapInterface $ldap, ModuleOptions $options)
    {
        $this->ldap      = $ldap;
        $this->options = $options;
        $entityClass = $this->options->getUserEntityClass();
        $this->entity = new $entityClass();

    }

    public function findByEmail($email)
    {
        $obj = $this->ldap->findByEmail($email);
        $this->entity->setDisplayName($obj['cn']['0']);
        $this->entity->setEmail($obj['mail']['0']);
        $this->entity->setId($obj['uidnumber']['0']);
        $this->entity->setUsername($obj['uid']['0']);
        return $this->entity;
    }

    public function findByUsername($username)
    {
        $obj = $this->ldap->findByUsername($username);
        $this->entity->setDisplayName($obj['cn']['0']);
        $this->entity->setEmail($obj['mail']['0']);
        $this->entity->setId($obj['uidnumber']['0']);
        $this->entity->setUsername($obj['uid']['0']);
        return $this->entity;
    }

    public function findById($id)
    {
        $obj = $this->ldap->findById($id);
        $this->entity->setDisplayName($obj['cn']['0']);
        $this->entity->setEmail($obj['mail']['0']);
        $this->entity->setId($obj['uidnumber']['0']);
        $this->entity->setUsername($obj['uid']['0']);
        return $this->entity;
    }
    public function authenticate($identity,$credential){
        return $this->ldap->authenticate($identity, $credential);
    }

    public function insert($entity, $tableName = null, HydratorInterface $hydrator = null)
    {
        return FALSE;
    }

    public function update($entity, $where = null, $tableName = null, HydratorInterface $hydrator = null)
    {
        return FALSE;
    }
}