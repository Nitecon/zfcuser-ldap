<?php
/**
 * This file is part of the ZfcUserLdap Module (https://github.com/Nitecon/zfcuser-ldap.git)
 *
 * Copyright (c) 2013 Will Hattingh (https://github.com/Nitecon/zfcuser-ldap)
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

class LdapInterface {

    private $config;
    protected $ldap;
    protected $entity;
    protected $active_server;
    protected $error;

    public function __construct($config) {
        $this->config = $config;
    }

    /**
     *
     * @param type $msg
     * @param type $log_level EMERG=0, ALERT=1, CRIT=2, ERR=3, WARN=4, NOTICE=5, INFO=6, DEBUG=7
     */
    public function log($msg, $priority = 5) {
        $log_dir = "data/logs";
        if (!is_dir($log_dir)) {
            try {
                mkdir($log_dir);
            } catch (Exception $exc) {
                echo "<h1>Could not create log directory " . $log_dir . "</h1>";
                echo $exc->getMessage();
            }
        }
        $logger = new Logger;
        $writer = new LogWriter($log_dir . '/ldap.log');
        $logger->addWriter($writer);
        Logger::registerErrorHandler($logger);
        $logger->log($priority, $msg);
    }

    public function bind() {
        $options = $this->config;
        /* We will try to loop through the list of servers
         * if no active servers are available then we will use the error msg
         */
        foreach ($options as $server) {
            try {
                $this->ldap = new \Zend\Ldap\Ldap($server);
                $this->ldap->bind();
                $this->active_server = $server;
            } catch (LdapException $exc) {
                $this->error = $exc->getMessage();
                continue;
            }
        }
    }

    public function findByUsername($username) {
        try {
            $this->bind();
        } catch (\Exception $exc) {
            return $this->error;
        }
        $entryDN = "uid=$username," . $this->active_server['baseDn'];
        try {
            $hm = $this->ldap->getEntry($entryDN);
            return $hm;
        } catch (LdapException $exc) {
            return $exc->getMessage();
        }
    }

    public function findByEmail($email) {
        try {
            $this->bind();
        } catch (\Exception $exc) {
            return $this->error;
        }
        try {
            $hm = $this->ldap->search("mail=$email", $this->active_server['baseDn'], \Zend\Ldap\Ldap::SEARCH_SCOPE_ONE);
            foreach ($hm as $item) {
                $this->log($item);
                return $item;
            }
            return FALSE;
        } catch (LdapException $exc) {
            $msg = $exc->getMessage();
            $this->log($msg);
            return $msg;
        }
    }

    public function findById($id) {
        try {
            $this->bind();
        } catch (\Exception $exc) {
            return $this->error;
        }
        try {
            $hm = $this->ldap->search("uidnumber=$id", $this->active_server['baseDn'], \Zend\Ldap\Ldap::SEARCH_SCOPE_ONE);
            foreach ($hm as $item) {
                $this->log($item);
                return $item;
            }
            return FALSE;
        } catch (LdapException $exc) {
            $msg = $exc->getMessage();
            $this->log($msg);
        }
    }

    function authenticate($username, $password) {
        try {
            $this->bind();
        } catch (\Exception $exc) {
            return $this->error;
        }
        $options = $this->config;
        $auth = new AuthenticationService();
        try {
            $adapter = new AuthAdapter($options, $username, $password);
            $result = $auth->authenticate($adapter);
            if ($result->isValid()) {
                $this->log("$username logged in successfully!");
                return TRUE;
            } else {
                $messages = $result->getMessages();
                return $messages;
            }
        } catch (LdapException $exc) {
            $msg = $exc->getMessage();
            $this->log($msg);
        }
    }

}