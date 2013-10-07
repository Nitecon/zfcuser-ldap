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

namespace ZfcUserLdap\ServiceFactory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream as LogWriter;

class LoggerAdapterFactory implements FactoryInterface
{

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('ZfcUserLdap\Config');
        $log_dir = $config['logging']['log_dir'];
        $log_filename = $config['logging']['log_filename'];
        if (!is_dir($log_dir)) {
            if (!mkdir($log_dir)) {
                throw new Exception("Unable to create Log directory: $log_dir");
            }
        }
        $logger = new Logger;
        $writer = new LogWriter($log_dir . '/' . $log_filename);
        $logger->addWriter($writer);
        return $logger;
    }
}
