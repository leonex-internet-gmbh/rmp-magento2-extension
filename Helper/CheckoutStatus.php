<?php

namespace Leonex\RiskManagementPlatform\Helper;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Leonex\RiskManagementPlatform\Helper\CheckoutStatus
 *
 * @author cstoller
 */
class CheckoutStatus extends AbstractHelper
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    public function __construct(Context $context, Session $_checkoutSession)
    {
        parent::__construct($context);
        $this->_checkoutSession = $_checkoutSession;
    }

    /**
     * Check if a payment has been selected during the checkout.
     *
     * @return bool
     */
    public function hasPaymentBeenSelected(): bool
    {
        $quote = $this->_checkoutSession->getQuote();
        
        if ($quote->currentPaymentWasSet()) {
            return true;
        }
        
        return $quote->getPayment() && $quote->getPayment()->getId();
    }

    /**
     * For logged in customers Magento creates the shipping and billing addresses
     * automatically after the customers enters the checkout process.
     * But we need to know when the customer really has setup his billing address.
     *
     * @return bool
     */
    public function hasBillingAddressReallyBeenSet(): bool
    {
        $quote = $this->_checkoutSession->getQuote();
        $billingAddress = $quote->getBillingAddress();

        if (!$billingAddress->getCompany() && !$billingAddress->getLastname() && !$billingAddress->getFirstname()) {
            return false;
        }

        $shippingAddress = $quote->getShippingAddress();

        // The billing address is deleted and a new model is generated
        // as soon as the customer has selected or entered the billing address.
        // So if the creation date is postponed, we now, that the billing address
        // has really been set by the user.
        return new \DateTime($billingAddress->getCreatedAt()) > new \DateTime($shippingAddress->getCreatedAt());
    }
}
