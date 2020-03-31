<?php

namespace Leonex\RiskManagementPlatform\Observer;

use Leonex\RiskManagementPlatform\Helper\Data;
use Leonex\RiskManagementPlatform\Model\Component\Connector;
use Leonex\RiskManagementPlatform\Model\Config\Source\CheckingTime;
use Leonex\RiskManagementPlatform\Model\Logger;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Model\MethodInterface;


class RestrictPayments implements ObserverInterface
{
    /**
     * @var bool
     */
    protected $isAfterPaymentMethodSelection = false;

    /**
     * @var Connector
     */
    protected $connector;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Logger
     */
    protected $rmpLogger;

    public function __construct(
        Data $helper,
        Connector $connector,
        Logger $rmpLogger
    ) {
        $this->connector = $connector;
        $this->helper = $helper;
        $this->rmpLogger = $rmpLogger;
    }

    public function execute(Observer $observer)
    {
        /** @var Event $event */
        $event = $observer->getEvent();

        if (!$event || !($event instanceof Event) || !($event->getMethodInstance() instanceof MethodInterface)) {
            return;
        }

        $paymentMethod = $event->getMethodInstance()->getCode();

        if ($this->helper->isDebugLoggingEnabled()) {
            $this->rmpLogger->debug('Observer for payment restriction called.', [
                'payment_method' => $paymentMethod,
                'event_name' => $event->getName(),
                'time_of_checking' => $this->helper->getTimeOfChecking(),
            ]);
        }

        // set flag to post checking time
        if ($event->getName() === 'sales_quote_payment_import_data_before') {
            $this->isAfterPaymentMethodSelection = true;
            return;
        }

        $checkingTime = $this->helper->getTimeOfChecking();

        // post check
        if ($checkingTime === CheckingTime::CHECKING_TIME_POST && !$this->isAfterPaymentMethodSelection) {
            // no need to check since the payment method is not yet selected
            return;
        }
        // pre check can also be applied in the post check, since the result is likely already cached

        if ($this->connector->isCheckNeeded($observer)) {
            $event->getResult()->setIsAvailable($this->connector->checkPaymentPre($paymentMethod));
        } else if ($this->helper->isDebugLoggingEnabled()) {
            $this->rmpLogger->debug('Check is not needed.');
        }
    }
}
