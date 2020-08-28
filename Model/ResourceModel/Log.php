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

    /**
     * At the time where the logs are saved to database only the quote ID is known.
     * This method can be used to assign the order ID to logs for a specified quote.
     *
     * @param int $quoteId
     * @param int $orderId
     * @return void
     */
    public function assignOrderIds(int $quoteId, int $orderId): void
    {
        $this->getConnection()->update(
            'rmp_log',
            ['order_id' => $orderId],
            ['quote_id = ?' => $quoteId, 'order_id IS NULL']
        );
    }
}
