<?php

namespace Leonex\RiskManagementPlatform\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Leonex\RiskManagementPlatform\Model\ResourceModel\Log
 */
class Log extends AbstractDb
{
    /**
     * {@inheritDoc}
     */
    protected $_serializableFields = [
        'payload' => [[], [], false],
    ];

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    protected function _construct(): void
    {
        $this->_init('rmp_log', 'log_id');
    }
}
