<?php

namespace ZfcUserLdap\Mapper;

use ZfcUser\Mapper\User as AbstractUserMapper;
use ZfcUser\Mapper\UserInterface;
use ZfcUserLdap\Mapper\UserHydrator as HydratorInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;

class User extends AbstractUserMapper implements UserInterface, ServiceManagerAwareInterface
{

    protected $tableName = 'user';

    public function findByEmail($email)
    {
        $select = $this->getSelect()->where(array('email' => $email));
        $entity = $this->select($select, $this->getEntity(), new HydratorInterface())->current();
        if (is_object($entity) && strlen($entity->getUsername()) > 0) {
            $this->getEventManager()->trigger('find', $this, array('entity' => $entity));
        }
        /* Now we select again so that it provides us with the ID as well
         * as assurance that the user made it into the database
         */
        $selectVerfify = $this->getSelect()->where(array('email' => $email));
        $verifiedEntity = $this->select($selectVerfify, $this->getEntity(), new HydratorInterface())->current();
        $this->getEventManager()->trigger('find', $this, array('entity' => $verifiedEntity));
        return $entity;
    }

    public function findByUsername($username)
    {
        $select = $this->getSelect()->where(array('username' => $username));
        $entity = $this->select($select, $this->getEntity(), new HydratorInterface())->current();
        if (is_object($entity) && strlen($entity->getUsername()) > 0) {
            $this->getEventManager()->trigger('find', $this, array('entity' => $entity));
        }
        /* Now we select again so that it provides us with the ID as well
         * as assurance that the user made it into the database
         */
        $selectVerfify = $this->getSelect()->where(array('username' => $username));
        $verifiedEntity = $this->select($selectVerfify, $this->getEntity(), new HydratorInterface())->current();
        $this->getEventManager()->trigger('find', $this, array('entity' => $verifiedEntity));
        return $entity;
    }

    public function findById($id)
    {
        $select = $this->getSelect()->where(array('user_id' => $id));
        $entity = $this->select($select)->current();
        $this->getEventManager()->trigger('find', $this, array('entity' => $entity));
        return $entity;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    public function insert($entity, $tableName = null, HydratorInterface $hydrator = null)
    {
        $result = parent::insert($entity, $tableName, $hydrator);
        $entity->setId($result->getGeneratedValue());
        return $result;
    }

    public function update($entity, $where = null, $tableName = null, HydratorInterface $hydrator = null)
    {
        if (!$where) {
            $where = 'user_id = ' . $entity->getId();
        }

        return parent::update($entity, $where, $tableName, $hydrator);
    }

    /**
     * Retrieve service manager instance
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Set service manager instance
     *
     * @param ServiceManager $locator
     * @return void
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    public function getEntity()
    {
        $options = $this->getServiceManager()->get('zfcuser_module_options');
        $entityClass = $options->getUserEntityClass();
        return new $entityClass;
    }

    /*
     * Creates a new User Entity
     * 
     * @return User Entity
     */

    public function newEntity($ldapObject)
    {
        $entity = $this->getEntity();
        if (isset($ldapObject['uid']['0'])) {
            $entity->setUsername($ldapObject['uid']['0']);
            $entity->setDisplayName($ldapObject['cn']['0']);
            $entity->setEmail($ldapObject['mail']['0']);
            $entity->setPassword(md5('HandledByLdap'));
            $entity->setRoles(serialize($this->getLdapRoles($ldapObject)));
        }
        return $entity;
    }

    /**
     * Insert or Update DB entry depending if a User Object is set.
     * 
     * @return User Entity
     */
    public function updateDb($ldapObject, $userObject)
    {
        if ($userObject == null) {
            $entity = $this->getEntity();
        } else {
            $entity = $userObject;
        }
        if (isset($ldapObject['uid']['0'])) {
            $entity->setUsername($ldapObject['uid']['0']);
            $entity->setDisplayName($ldapObject['cn']['0']);
            $entity->setEmail($ldapObject['mail']['0']);
            $entity->setPassword(md5('HandledByLdap'));
            $entity->setRoles(serialize($this->getLdapRoles($ldapObject)));
            if ($userObject == null) {
                $this->insert($entity, $this->tableName, new HydratorInterface());
            } else {
                $this->update($entity, null, $this->tableName, new HydratorInterface());
            }
        }
        return $entity;
    }

    public function getLdapRoles($ldapObject)
    {
        $roles = array();
        $config = $this->getServiceManager()->get('ZfcUserLdap\Config');
        $roleKey = $config['identity_providers']['ldap_role_key'];

        foreach ($ldapObject[$roleKey] as $role) {
            if (in_array($role, $config['identity_providers']['usable_roles'])) {
                $roles[] = $role;
            }
        }
        return $roles;
    }
}
