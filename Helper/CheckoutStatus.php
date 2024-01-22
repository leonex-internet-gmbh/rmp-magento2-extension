<?php

namespace Leonex\RiskManagementPlatform\Helper;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Api\Data\CartInterface;

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
        $payment = $quote->getPayment();

        return $payment && ($payment->getId() || $payment->getMethod());
    }

    /**
     * Check whether a billing address has been provided.
     */
    public function isAddressProvided(CartInterface $quote): bool
    {
        $billingAddress = $quote->getBillingAddress();

        return $billingAddress->getCompany()
            || $billingAddress->getLastname()
            || $billingAddress->getFirstname();
    }

    /**
     * Magento creates the shipping and billing addresses automatically after the customers enters the checkout process.
     * But we need to know when the customer really has entered his billing address.
     *
     * @return bool
     */
    public function hasBillingAddressReallyBeenSet(?CartInterface $quote = null): bool
    {
        if (!$quote) {
            trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Not passing the quote model is deprecated and will fail in 3.0.0.');
            $quote = $this->_checkoutSession->getQuote();
        }

        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        // The billing address is deleted and a new model is generated
        // as soon as the customer has selected or entered the billing address.
        // So if the creation date is postponed, we know, that the billing address
        // has really been set by the user.
        return !$billingAddress->getUpdatedAt() || $billingAddress->getUpdatedAt() > $shippingAddress->getCreatedAt(); // comparison of the date string is working fine
    }

    /**
     * Save in the session that the order was tried to be placed.
     */
    public function setTriedToPlaceOrder(bool $tried): void
    {
        $this->_checkoutSession->setStepData('place_order', 'tried_to_place_order', $tried);
    }

    /**
     * Check if the order was already tried to be saved during this checkout.
     */
    public function hasOrderBeenTriedToPlace(): bool
    {
        return $this->_checkoutSession->getStepData('place_order', 'tried_to_place_order');
    }
}
