<?php

namespace Leonex\RiskManagementPlatform\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{

    const XML_PATH = 'leonex_rmp/settings/';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var State
     */
    protected $state;

    protected $paymentHelper;

    /**
     * Data constructor.
     *
     * @param Context                $context
     * @param StoreManagerInterface  $storeManager
     * @param State                  $state
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        State $state,
        PaymentHelper $paymentHelper
    ) {
        $this->storeManager = $storeManager;
        $this->state = $state;
        $this->paymentHelper = $paymentHelper;
        parent::__construct($context);
    }

    /**
     * Get config value from given key
     *
     * @param      $code
     * @param null $storeId
     *
     * @return mixed
     */
    protected function getConfigValue($code, $storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH . $code, ScopeInterface::SCOPE_STORE, $storeId);
    }

    protected function getConfigFlag($code, $storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH . $code, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get value time of checking
     * pre | post
     *
     * @return mixed
     */
    public function getTimeOfChecking()
    {
        return $this->getConfigValue('time_of_checking');
    }

    /**
     * Get api-url
     *
     * @return mixed
     */
    public function getApiUrl()
    {
        return $this->getConfigValue('apiurl');
    }

    /**
     * Get api-key
     *
     * @return mixed
     */
    public function getApiKey()
    {
        return trim($this->getConfigValue('apikey'));
    }

    /**
     * Check if allow to check payments
     */
    public function isActive(): bool
    {
        $return = $this->getConfigValue('is_active');
        return !!$return;
    }

    /**
     * Check if admin context
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isAdmin()
    {
        return 'adminhtml' === $this->state->getAreaCode();
    }

    /**
     * Get the payment methods codes that should be checked.
     *
     * @return array
     */
    public function getPaymentMethodsToCheck(): array
    {
        $value = $this->getConfigValue('payment_methods_to_check');
        if (trim($value)) {
            return explode(',', $value);
        }

        // If no payment method has been selected, we'll check all.
        // This is no problem because only those that are configured in the RMP
        // have a chance to be disabled.
        return array_keys($this->paymentHelper->getPaymentMethodList(true));
    }

    /**
     * Get the Maximum order grand total if the RMP is unavailable.
     *
     * @return float
     */
    public function getMaxGrandTotalWhenOffline(): float
    {
        return (float) $this->getConfigValue('max_grand_total_when_offline');
    }
}
