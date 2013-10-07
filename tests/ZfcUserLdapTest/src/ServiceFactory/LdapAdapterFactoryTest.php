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

namespace ZfcUserLdapTest\ServiceFactory;

use Zend\Mvc\Application;

class LdapAdapterFactoryTest extends \PHPUnit_Framework_TestCase
{

    protected $service_locator;
    protected $application;
    protected $applicationConfig;

    /**
     * Prepare the object to be tested.
     */
    protected function setUp()
    {
        $config = include ("./tests/config/application.config.php");
        $this->applicationConfig = $config;
        $this->application = $this->getApplication();
    }

    public function testGetInstantiatedClassFromFactory()
    {
        $ldapAdapter = $this->application->getServiceManager()->get("ZfcUserLdap\LdapAdapter");
        $this->assertEquals(get_class($ldapAdapter), 'ZfcUserLdap\Adapter\Ldap');
    }

    /**
     * Get the application object
     * @return \Zend\Mvc\ApplicationInterface
     */
    public function getApplication()
    {
        if ($this->application) {
            return $this->application;
        }
        $appConfig = $this->applicationConfig;
        //Console::overrideIsConsole($this->getUseConsoleRequest());
        $this->application = Application::init($appConfig);

        $events = $this->application->getEventManager();
        $events->detach($this->application->getServiceManager()->get('SendResponseListener'));

        return $this->application;
    }
}
