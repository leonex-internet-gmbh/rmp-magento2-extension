<?php

namespace Leonex\RiskManagementPlatform\Plugin\Checkout\Model;

use Leonex\RiskManagementPlatform\Helper\CheckoutStatus;
use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

class GuestPaymentInformationManagementPlugin
{
    /** @var CheckoutStatus */
    protected $checkoutStatusHelper;

    public function __construct(CheckoutStatus $checkoutStatusHelper)
    {
        $this->checkoutStatusHelper = $checkoutStatusHelper;
    }

    public function beforeSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagementInterface $paymentInformationManagement,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): void {
        $this->checkoutStatusHelper->setTriedToPlaceOrder(true);
    }
}
