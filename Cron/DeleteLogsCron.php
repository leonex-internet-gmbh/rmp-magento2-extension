<?php

namespace Leonex\RiskManagementPlatform\Cron;

use Leonex\RiskManagementPlatform\Helper\Data;
use Leonex\RiskManagementPlatform\Helper\Logging;
use Magento\Framework\App\ResourceConnection;

/**
 * Leonex\RiskManagementPlatform\Cron\DeleteLogsCron
 */
class DeleteLogsCron
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var Logging
     */
    protected $_loggingHelper;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    public function __construct(Data $helper, Logging $loggingHelper, ResourceConnection $resource)
    {
        $this->_helper = $helper;
        $this->_loggingHelper = $loggingHelper;
        $this->_resource = $resource;
    }

    /**
     * Delete all price values for non-admin stores if PRICE_SCOPE is set to global.
     *
     * @return void
     */
    public function execute()
    {
        $storageDuration = $this->_loggingHelper->getStorageDurationInDays();

        if (!$this->_helper->isActive() || $storageDuration <= 0) {
            return;
        }

        $offsetDate = new \DateTime("-$storageDuration days");

        $connection = $this->_resource->getConnection();
        $connection->delete('rmp_log', ['created_at < ?' => $offsetDate->format('Y-m-d')]);
    }
}
