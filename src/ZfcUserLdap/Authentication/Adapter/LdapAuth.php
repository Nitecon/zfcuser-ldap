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
use Zend\Authentication\Exception\UnexpectedValueException as UnexpectedExc;

class LdapAuth extends AbstractAdapter implements ServiceManagerAwareInterface
{

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

    public function authenticate(AuthEvent $e)
    {
        $userObject = null;
        $zulConfig = $this->serviceManager->get('ZfcUserLdap\Config');

        if ($this->isSatisfied()) {
            $storage = $this->getStorage()->read();
            $e->setIdentity($storage['identity'])
                    ->setCode(AuthenticationResult::SUCCESS)
                    ->setMessages(array('Authentication successful.'));
            return;
        }

        // Get POST values
        $identity = $e->getRequest()->getPost()->get('identity');
        $credential = $e->getRequest()->getPost()->get('credential');

        // Start auth against LDAP
        $ldapAuthAdapter = $this->serviceManager->get('ZfcUserLdap\LdapAdapter');
        if ($ldapAuthAdapter->authenticate($identity, $credential) !== true) {
            // Password does not match
            $e->setCode(AuthenticationResult::FAILURE_CREDENTIAL_INVALID)
                    ->setMessages(array('Supplied credential is invalid.'));
            $this->setSatisfied(false);
            return false;
        }
        $validator = new EmailAddress();
        if ($validator->isValid($identity)) {
            $ldapObj = $ldapAuthAdapter->findByEmail($identity);
        } else {
            $ldapObj = $ldapAuthAdapter->findByUsername($identity);
        }
        if (!is_array($ldapObj)) {
            throw new UnexpectedExc('Ldap response is invalid returned: ' . var_export($ldapObj, true));
        }
        // LDAP auth Success!

        $fields = $this->getOptions()->getAuthIdentityFields();

        // Create the user object entity via the LDAP object
        $userObject = $this->getMapper()->newEntity($ldapObj);

        // If auto insertion is on, we will check against DB for existing user,
        // then will create or update user depending on results and settings
        if ($zulConfig['auto_insertion']['enabled']) {
            $validator = new EmailAddress();
            if ($validator->isValid($identity)) {
                $userDbObject = $this->getMapper()->findByEmail($identity);
            } else {
                $userDbObject = $this->getMapper()->findByUsername($identity);
            }

            if ($userDbObject === false) {
                $userObject = $this->getMapper()->updateDb($ldapObj, null);
            } elseif ($zulConfig['auto_insertion']['auto_update']) {
                $userObject = $this->getMapper()->updateDb($ldapObj, $userDbObject);
            } else {
                $userObject = $userDbObject;
            }
        }

        // Something happened that should never happen
        if (!$userObject) {
            $e->setCode(AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND)
                    ->setMessages(array('A record with the supplied identity could not be found.'));
            $this->setSatisfied(false);
            return false;
        }

        // We don't control state, however if someone manually alters
        // the DB, this will throw the code then
        if ($this->getOptions()->getEnableUserState()) {
            // Don't allow user to login if state is not in allowed list
            if (!in_array($userObject->getState(), $this->getOptions()->getAllowedLoginStates())) {
                $e->setCode(AuthenticationResult::FAILURE_UNCATEGORIZED)
                        ->setMessages(array('A record with the supplied identity is not active.'));
                $this->setSatisfied(false);
                return false;
            }
        }

        // Set the roles for stuff like ZfcRbac
        $userObject->setRoles($this->getMapper()->getLdapRoles($ldapObj));
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

    /**
     * getMapper
     *
     * @return UserMapperInterface
     */
    public function getMapper()
    {
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
    public function setMapper(UserMapperInterface $mapper)
    {
        $this->mapper = $mapper;
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

    /**
     * @return AuthenticationOptionsInterface
     */
    public function getEntity()
    {
        $entityClass = $this->getOptions()->getUserEntityClass();
        $this->entity = new $entityClass;
        return $this->entity;
    }
}
