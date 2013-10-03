<?php

namespace ZfcUserLdap\Authentication\Adapter;

use Zend\Authentication\Result as AuthenticationResult;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use ZfcUser\Authentication\Adapter\AbstractAdapter;
use ZfcUser\Authentication\Adapter\AdapterChainEvent as AuthEvent;
use ZfcUserLdap\Mapper\User as UserMapperInterface;
use ZfcUser\Options\AuthenticationOptionsInterface;
use ZfcUserLdap\Mapper\UserHydrator;
use Zend\Validator\EmailAddress;

class LdapAuth extends AbstractAdapter implements ServiceManagerAwareInterface {

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

    /** @var ZfcUserLdap\Entity\User */
    protected $entity;

    public function authenticate(AuthEvent $e) {
        if ($this->isSatisfied()) {
            $storage = $this->getStorage()->read();
            $e->setIdentity($storage['identity'])
                    ->setCode(AuthenticationResult::SUCCESS)
                    ->setMessages(array('Authentication successful.'));
            return;
        }

        $identity = $e->getRequest()->getPost()->get('identity');
        $credential = $e->getRequest()->getPost()->get('credential');
        //$credential = $this->preProcessCredential($credential);

        $userObject = NULL;
        // Cycle through the configured identity sources and test each
        $fields = $this->getOptions()->getAuthIdentityFields();
        if (in_array('email', $fields)) {
            $validator = new EmailAddress();
            if ($validator->isValid($identity)) {
                $userObject = $this->getMapper()->findByEmail($identity);
            } else {
                $userObject = $this->getMapper()->findByUsername($identity);
            }

        }

        if (!$userObject) {
            $e->setCode(AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND)
                    ->setMessages(array('A record with the supplied identity could not be found.'));
            $this->setSatisfied(false);
            return false;
        }

        if ($this->getOptions()->getEnableUserState()) {
            // Don't allow user to login if state is not in allowed list
            if (!in_array($userObject->getState(), $this->getOptions()->getAllowedLoginStates())) {
                $e->setCode(AuthenticationResult::FAILURE_UNCATEGORIZED)
                        ->setMessages(array('A record with the supplied identity is not active.'));
                $this->setSatisfied(false);
                return false;
            }
        }
        $ldapAuthAdapter = $this->serviceManager->get('ZfcUserLdap\LdapAdapter');
        if (!$ldapAuthAdapter->authenticate($identity, $credential)) {
            // Password does not match
            $e->setCode(AuthenticationResult::FAILURE_CREDENTIAL_INVALID)
                    ->setMessages(array('Supplied credential is invalid.'));
            $this->setSatisfied(false);
            return false;
        }
        /* Since LDAP can change without us knowing about it we should update
         * the database with most recent details on login
         */
        $this->updateLocalDBDetails($identity, $ldapAuthAdapter, $userObject);
        // Success!
        $e->setIdentity($userObject);

        $this->setSatisfied(true);
        $storage = $this->getStorage()->read();
        $storage['identity'] = $userObject;
        $this->getStorage()->write($storage);
        $e->setCode(AuthenticationResult::SUCCESS)
                ->setMessages(array('Authentication successful.'))
                ->stopPropagation();
    }

    protected function updateLocalDBDetails($identity, $ldapAuthAdapter, $userObject) {
        $validator = new EmailAddress();
        if ($validator->isValid($identity)) {
            $ldapObj = $ldapAuthAdapter->findByEmail($identity);
        } else {
            $ldapObj = $ldapAuthAdapter->findByUsername($identity);
        }

        if (isset($ldapObj['uid']['0'])) {
            $userObject->setUsername($ldapObj['uid']['0']);
            $userObject->setDisplayName($ldapObj['cn']['0']);
            $userObject->setEmail($ldapObj['mail']['0']);
            $userObject->setPassword(md5('HandledByLdap'));
            $userObject->setRawLdapObj(serialize($ldapObj));

            $this->getMapper()->update($userObject, \NULL, $this->getMapper()->getTableName(), new UserHydrator());
        }
    }

    protected function updateUserPasswordHash($userObject, $password, $bcrypt) {
        $hash = explode('$', $userObject->getPassword());
        if ($hash[2] === $bcrypt->getCost())
            return;
        $userObject->setPassword($bcrypt->create($password));
        $this->getMapper()->update($userObject);
        return $this;
    }

    /**
     * getMapper
     *
     * @return UserMapperInterface
     */
    public function getMapper() {
        if (null === $this->mapper) {
            $this->mapper = $this->getServiceManager()->get('ZfcUserLdap\Mapper');
        }
        return $this->mapper;
    }

    /**
     * setMapper
     *
     * @param UserMapperInterface $mapper
     * @return LdapAuth
     */
    public function setMapper(UserMapperInterface $mapper) {
        $this->mapper = $mapper;
        return $this;
    }

    /**
     * Retrieve service manager instance
     *
     * @return ServiceManager
     */
    public function getServiceManager() {
        return $this->serviceManager;
    }

    /**
     * Set service manager instance
     *
     * @param ServiceManager $locator
     * @return void
     */
    public function setServiceManager(ServiceManager $serviceManager) {
        $this->serviceManager = $serviceManager;
    }

    /**
     * @param AuthenticationOptionsInterface $options
     */
    public function setOptions(AuthenticationOptionsInterface $options) {
        $this->options = $options;
    }

    /**
     * @return AuthenticationOptionsInterface
     */
    public function getOptions() {
        if (!$this->options instanceof AuthenticationOptionsInterface) {
            $this->setOptions($this->getServiceManager()->get('zfcuser_module_options'));
        }
        return $this->options;
    }

    /**
     * @return AuthenticationOptionsInterface
     */
    public function getEntity() {
        $entityClass = $this->getOptions()->getUserEntityClass();
        $this->entity = new $entityClass;
        return $this->entity;
    }

}
