<?php

namespace Leonex\RiskManagementPlatform\Observer;

use Leonex\RiskManagementPlatform\Model\ResourceModel\Log as LogResource;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

/**
 * Description of SaveOrderIdToLogs
 *
 * @author cstoller
 */
class SaveOrderIdToLogs implements ObserverInterface
{
    protected $logResource;

    public function __construct(LogResource $logResource)
    {
        $this->logResource = $logResource;
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getOrder();
        if (!$order instanceof Order) {
            throw new InvalidArgumentException('Invalid or no observer order parameter required.');
        }

        $this->logResource->assignOrderIds($order->getQuoteId(), $order->getId());
    }
}
