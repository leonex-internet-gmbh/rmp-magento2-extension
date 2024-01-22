<?php

namespace Leonex\RiskManagementPlatform\Observer;

use Leonex\RiskManagementPlatform\Helper\CheckoutStatus;
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
    /** @var CheckoutStatus */
    protected $checkoutStatusHelper;

    protected $logResource;

    public function __construct(CheckoutStatus $checkoutStatusHelper, LogResource $logResource)
    {
        $this->checkoutStatusHelper = $checkoutStatusHelper;
        $this->logResource = $logResource;
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getOrder();
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('Invalid or no observer order parameter required.');
        }

        $this->logResource->assignOrderIds($order->getQuoteId(), $order->getId());

        // Reset session
        $this->checkoutStatusHelper->setTriedToPlaceOrder(false);
    }
}
