<?php
/**
 * This file is part of the ZfcUserLdap Module (https://github.com/Enrise/ZfcUser-Ldap)
 *
 * Copyright (c) 2013 Rob Quist (https://github.com/RobQuistNL)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 */
namespace ZfcUserLdap\Options;

use ZfcUser\Options\ModuleOptions as BaseModuleOptions;

class ModuleOptions extends BaseModuleOptions {

    /**
     * @var string
     */
    protected $userEntityClass = 'ZfcUserLdap\Entity\User';

    /**
     * @var bool
     */
    protected $enableDefaultEntities = true;

    /**
     * @param boolean $enableDefaultEntities
     */
    public function setEnableDefaultEntities($enableDefaultEntities) 
    {
        $this->enableDefaultEntities = $enableDefaultEntities;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getEnableDefaultEntities() 
    {
        return $this->enableDefaultEntities;
    }

}