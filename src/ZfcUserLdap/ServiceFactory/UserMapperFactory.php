<?php

/**
 * ZfcUserLdap Module
 *
 * @package    ZfcUserLdap
 */

namespace ZfcUserLdap\ServiceFactory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\Hydrator;

/**
 * @package    ZfcUserLdap
 */
class UserMapperFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $services)
    {
        $options = $services->get('zfcuser_module_options');

        $mapper = new \ZfcUserLdap\Mapper\User();
        $entityClass = $options->getUserEntityClass();
        $mapper->setEntityPrototype(new $entityClass);
        $mapper->setDbAdapter($services->get('zfcuser_zend_db_adapter'));
        $mapper->setHydrator(new Hydrator\ClassMethods);

        return $mapper;
    }
}
