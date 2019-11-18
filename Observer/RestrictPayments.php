<?php

namespace Leonex\RiskManagementPlatform\Observer;

use Leonex\RiskManagementPlatform\Model\Component\Connector;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Model\MethodInterface;


class RestrictPayments implements ObserverInterface
{
    protected $_connector;

    public function __construct(
        Connector $connector
    ) {
        $this->_connector = $connector;
    }

    public function execute(Observer $observer)
    {
        /** @var Event $event */
        $event = $observer->getEvent();

        if (!$event || !($event instanceof Event)) {
            return;
        }

        if (!$event->getMethodInstance() || !($event->getMethodInstance() instanceof MethodInterface)) {
            return;
        }

        if ($this->_connector->verifyInterest($observer)) {
            $event->getResult()->setIsAvailable($this->_connector->checkPaymentPre($observer));
        }
    }
}
