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

namespace ZfcUserLdap\Adapter;

use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\Ldap as AuthAdapter;
use Zend\Ldap\Exception\LdapException;

class Ldap {

    private $config;

    /** @var Zend\Ldap\Ldap */
    protected $ldap;

    /**
     * Array of server configuration options, active server is
     * set to the first server that is able to bind successfully 
     * @var array */
    protected $active_server;

    /**
     * An array of error messages.
     * @var array
     */
    protected $error = array();

    /**
     * Log writer
     * @var Zend\Log\Logger
     */
    protected $logger;

    /** @var bool */
    protected $logEnabled;

    public function __construct($config, $logger, $logEnabled) {
        $this->config = $config;
        $this->logger = $logger;
        $this->logEnabled = $logEnabled;
    }

    /**
     *
     * @param type $msg
     * @param type $log_level EMERG=0, ALERT=1, CRIT=2, ERR=3, WARN=4, NOTICE=5, INFO=6, DEBUG=7
     */
    public function log($msg, $priority = 5) {
        if ($this->logEnabled) {
            if (!is_string($msg)) {
                $this->logger->log($priority, var_export($msg, true));
            } else {
                $this->logger->log($priority, $msg);
            }
        }
    }

    public function bind() {
        $options = $this->config;
        /* We will try to loop through the list of servers
         * if no active servers are available then we will use the error msg
         */
        foreach ($options as $server) {
            $this->log("Attempting bind with ldap");
            try {
                $this->ldap = new \Zend\Ldap\Ldap($server);
                $this->ldap->bind();
                $this->log("Bind successful setting active server.");
                $this->active_server = $server;
            } catch (LdapException $exc) {
                $this->error[] = $exc->getMessage();
                continue;
            }
        }
    }

    public function findByUsername($username) {
        $this->bind();
        $entryDN = "uid=$username," . $this->active_server['baseDn'];
        $this->log("Attempting to get username entry: $entryDN against the active ldap server");
        try {
            $hm = $this->ldap->getEntry($entryDN);
            $this->log("Raw Ldap Object: ".var_export($hm,true),7);
            $this->log("Username entry lookup response: ".var_export($hm,true));
            return $hm;
        } catch (LdapException $exc) {
            return $exc->getMessage();
        }
    }

    public function findByEmail($email) {
        $this->bind();
        $this->log("Attempting to search ldap by email for $email against the active ldap server");
        try {
            $hm = $this->ldap->search("mail=$email", $this->active_server['baseDn'], \Zend\Ldap\Ldap::SEARCH_SCOPE_ONE);
            $this->log("Raw Ldap Object: ".var_export($hm,true),7);
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
        $this->bind();
        $this->log("Attempting to search ldap by uidnumber for $id against the active ldap server");
        try {
            $hm = $this->ldap->search("uidnumber=$id", $this->active_server['baseDn'], \Zend\Ldap\Ldap::SEARCH_SCOPE_ONE);
            $this->log("Raw Ldap Object: ".var_export($hm,true),7);
            foreach ($hm as $item) {
                $this->log($item);
                return $item;
            }
            return \FALSE;
        } catch (LdapException $exc) {
            $msg = $exc->getMessage();
            $this->log($msg);
        }
    }

    function authenticate($username, $password) {
        $this->bind();
        $options = $this->config;
        $auth = new AuthenticationService();
        $this->log("Attempting to authenticate $username");
        $adapter = new AuthAdapter($options, $username, $password);
        $result = $auth->authenticate($adapter);
        if ($result->isValid()) {
            $this->log("$username logged in successfully!");
            return TRUE;
        } else {
            $messages = $result->getMessages();
            $this->log("$username AUTHENTICATION FAILED!, error output: ".var_export($messages,true));
            return $messages;
        }
    }

}