<?php
/**
 * This file is part of the ZfcUserLdap Module (https://github.com/Enrise/zfcuser-ldap)
 *
 * Copyright (c) 2013 Rob Quist (https://github.com/Enrise)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 */
namespace ZfcUserLdap\Authentication\Adapter;

use ZfcUserLdap\Mapper\User as UserMapperInterface;
use ZfcUserLdap\Entity\User as UserEntity;
use Zend\Authentication\Storage;
use Zend\Authentication\Result as AuthenticationResult;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use ZfcUser\Options\AuthenticationOptionsInterface;
use ZfcUser\Authentication\Adapter\ChainableAdapter as AdapterChain;
use ZfcUser\Authentication\Adapter\AdapterChainEvent as AuthEvent;

class Ldap implements AdapterChain, ServiceManagerAwareInterface {

    /**
     * @var UserMapperInterface
     */
    protected $mapper;

    /**
     * @var closure / invokable object
     */
    protected $credentialPreprocessor;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var AuthenticationOptionsInterface
     */
    protected $options;

    /**
     * @var Storage\StorageInterface
     */
    protected $storage;
    
    /**
     * Initiate our own UserMapper
     * @return \ZfcUserLdap\Mapper\User
     */
    private function createMapper() 
    {
        $config = $this->getServiceManager()->get('Config');
        $mapper = new \ZfcUserLdap\Mapper\User(
                $this->getServiceManager()->get('ldap_interface'), $this->getServiceManager()->get('zfcuser_module_options'), $config['ldap']
        );
        
        $this->setMapper($mapper);
        
        return $this->getMapper();
    }
    
    /**
     * Create a ZfcUser user entity with values from an ZfcUserLdap entity
     * @param ZfcUserLdap\Entity\User $userEntity
     * @return \ZfcUser\Entity\User
     */
    private function populateUserDbObject(UserEntity $userEntity) 
    {
        
        $userDbObject = new \ZfcUser\Entity\User();
        
        $userDbObject->setUsername($userEntity->getUsername());
        $userDbObject->setEmail($userEntity->getEmail());
        $userDbObject->setDisplayName($userEntity->getDisplayName());
        $userDbObject->setPassword(''); //Otherwise the query won't work.
        
        return $userDbObject;
    }
    
    /**
     * authenticate Function.
     * Fired by ChainableAdapter.
     * 
     * Checks the user login via LDAP, and adds user into DB if he exists.
     * 
     * @see \ZfcUser\Authentication\Adapter\ChainableAdapter::authenticate()
     */
    public function authenticate(AuthEvent $e) 
    {

        $this->createMapper();

        $identity = $e->getRequest()->getPost()->get('identity');
        $credential = $e->getRequest()->getPost()->get('credential');
        
        $userObject = $this->getMapper()->authenticate($identity, $credential);
        if ($userObject === false) {
            // Password does not match
            $e->setCode(AuthenticationResult::FAILURE_CREDENTIAL_INVALID)
                    ->setMessages(array($userObject));
            $this->setSatisfied(false);
            return false;
        }

        $userEntity = $userObject->getEntity();
        $e->setIdentity($userEntity);
        
        $userDbMapper = $this->serviceManager->get('zfcuser_user_db_mapper');
        

        $fetchFromDb = $userDbMapper->findByUsername($userEntity->getUsername());
        
        if ($fetchFromDb === false) {
            //This user has been logged in, but he's not yet in the database.
            $userDbObject = $this->populateUserDbObject($userEntity);
            $returnedEntity = $userDbMapper->insert($userDbObject, 'user');
            $userEntity->setId($userDbObject->getId());
        } else {
            $userEntity->setId($fetchFromDb->getId());
        }

        $this                ->setSatisfied(true);
        $storage             = $this->getStorage()->read();
        $storage['identity'] = $e->getIdentity();
        $this->getStorage()  ->write($storage);

        $e->setCode(AuthenticationResult::SUCCESS)
          ->setMessages(array('Authentication successful.'));
    }

    /**
     * Returns the persistent storage handler
     *
     * Session storage is used by default unless a different storage adapter has been set.
     *
     * @return Storage\StorageInterface
     */
    public function getStorage() 
    {
            
        if (null === $this->storage) {
            $this->setStorage(new Storage\Session(get_called_class()));
        }

        return $this->storage;
    }

    /**
     * Sets the persistent storage handler
     *
     * @param  Storage\StorageInterface $storage
     * @return AbstractAdapter Provides a fluent interface
     */
    public function setStorage(Storage\StorageInterface $storage) 
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * Check if this adapter is satisfied or not
     *
     * @return bool
     */
    public function isSatisfied() 
    {
        
        $storage = $this->getStorage()->read();
        return (isset($storage['is_satisfied']) && true === $storage['is_satisfied']);
    }

    /**
     * Set if this adapter is satisfied or not
     *
     * @param bool $bool
     * @return AbstractAdapter
     */
    public function setSatisfied($bool = true) 
    {
        $storage = $this->getStorage()->read() ? : array();
        $storage['is_satisfied'] = $bool;
        $this->getStorage()->write($storage);
        return $this;
    }

    public function preprocessCredential($credential) 
    {
        $processor = $this->getCredentialPreprocessor();
        if (is_callable($processor)) {
            return $processor($credential);
        }
        return $credential;
    }

    /**
     * getMapper
     *
     * @return UserMapperInterface
     */
    public function getMapper() 
    {
        if (null === $this->mapper) {
            $this->mapper = $this->getServiceManager()->get('zfcuser_user_mapper');
        }
        return $this->mapper;
    }

    /**
     * setMapper
     *
     * @param UserMapperInterface $mapper
     * @return Db
     */
    public function setMapper(UserMapperInterface $mapper) 
    {
        $this->mapper = $mapper;
        return $this;
    }

    /**
     * Get credentialPreprocessor.
     *
     * @return \callable
     */
    public function getCredentialPreprocessor() 
    {
        return $this->credentialPreprocessor;
    }

    /**
     * Set credentialPreprocessor.
     *
     * @param $credentialPreprocessor the value to be set
     */
    public function setCredentialPreprocessor($credentialPreprocessor) 
    {
        $this->credentialPreprocessor = $credentialPreprocessor;
        return $this;
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

    /**
     * @param AuthenticationOptionsInterface $options
     */
    public function setOptions(AuthenticationOptionsInterface $options) 
    {
        $this->options = $options;
    }

    /**
     * @return AuthenticationOptionsInterface
     */
    public function getOptions() 
    {
        if (!$this->options instanceof AuthenticationOptionsInterface) {
            $this->setOptions($this->getServiceManager()->get('zfcuser_module_options'));
        }
        return $this->options;
    }

}