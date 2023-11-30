<?php

namespace Leonex\RiskManagementPlatform\Plugin\Customer\Api;

use Magento\Checkout\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;

/**
 * Leonex\RiskManagementPlatform\Plugin\Customer\Api\SafeGuestEmailPlugin
 *
 * Saves the guest's email address in the session. Otherwise it is not possible
 * to access it before the checkout process is completed.
 *
 * @author cstoller
 */
class SafeGuestEmailPlugin
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    public function __construct(Session $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Check if given email is associated with a customer account in given website.
     *
     * @param string $customerEmail
     * @return bool
     */
    public function afterIsEmailAvailable(AccountManagementInterface $accountManagement, $result, $customerEmail)
    {
        $this->checkoutSession->setStepData('shipping_address', 'leonex.rmp.email', $customerEmail);
        return $result;
    }
}
