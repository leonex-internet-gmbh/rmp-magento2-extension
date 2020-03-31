<?php

namespace Leonex\RiskManagementPlatform\Model;

use Monolog\Logger as BaseLogger;
use Magento\Framework\Logger\Handler\System as SystemLoggerHandler;

/**
 * Leonex\RiskManagementPlatform\Model\Logger
 *
 * @author cstoller
 */
class Logger extends BaseLogger
{
    /**
     * {@inheritDoc}
     */
    public function __construct($name, array $handlers = array(), array $processors = array())
    {
        parent::__construct($name, $handlers, $processors);

        foreach ($handlers as $handler) {
            if ($handler instanceof SystemLoggerHandler) {
                // only logs with level >= NOTICE should be logged to the system handler
                $handler->setLevel(BaseLogger::NOTICE);
            }
        }
    }
}
