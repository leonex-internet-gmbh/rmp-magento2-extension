<?php

namespace Leonex\RiskManagementPlatform\Observer;

use Leonex\RiskManagementPlatform\Helper\Data;
use Leonex\RiskManagementPlatform\Helper\CheckoutStatus;
use Leonex\RiskManagementPlatform\Helper\Logging;
use Leonex\RiskManagementPlatform\Model\Component\Connector;
use Leonex\RiskManagementPlatform\Model\Config\Source\CheckingTime;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Model\MethodInterface;


class RestrictPayments implements ObserverInterface
{
    protected $checkoutSession;

    /**
     * @var Connector
     */
    protected $connector;

    /**
     * @var Data
     */
    protected $helper;

    protected $checkoutStatusHelper;

    /**
     * @var Logging
     */
    protected $loggingHelper;

    public function __construct(
        Session $checkoutSession,
        Data $helper,
        CheckoutStatus $checkoutStatusHelper,
        Logging $loggingHelper,
        Connector $connector
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->connector = $connector;
        $this->helper = $helper;
        $this->checkoutStatusHelper = $checkoutStatusHelper;
        $this->loggingHelper = $loggingHelper;
    }

    public function execute(Observer $observer)
    {
        /** @var Event $event */
        $event = $observer->getEvent();

        if (!$event || !($event instanceof Event) || !($event->getMethodInstance() instanceof MethodInterface)) {
            return;
        }

        $paymentMethod = $event->getMethodInstance()->getCode();
        $quoteId = $this->checkoutSession->getQuoteId();

        // debug logging
        $msg = sprintf('Observer for payment restriction called for method "%s".', $paymentMethod);
        $this->loggingHelper->log('debug', $msg, 'observer', [
            'payment_method' => $paymentMethod,
            'time_of_checking' => $this->helper->getTimeOfChecking(),
        ], $quoteId);

        $checkingTime = $this->helper->getTimeOfChecking();

        // post check
        if ($checkingTime === CheckingTime::CHECKING_TIME_POST && !$this->checkoutStatusHelper->hasPaymentBeenSelected()) {
            // Debug logging
            $this->loggingHelper->log('debug', 'No payment method selected, yet.', 'observer', [
                'time_of_checking' => $this->helper->getTimeOfChecking(),
            ], $quoteId);

            // no need to check since the payment method is not yet selected
            return;
        }
        // pre check can also be applied in the post check, since the result is likely already cached

        if ($this->connector->isCheckNeeded($observer)) {
            $event->getResult()->setIsAvailable($this->connector->checkPaymentPre($paymentMethod));
        }
    }
}
