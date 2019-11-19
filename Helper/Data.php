<?php

namespace Leonex\RiskManagementPlatform\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
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
        State $state
    ) {
        $this->storeManager = $storeManager;
        $this->state = $state;
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
        return $this->getConfigValue('apikey');
    }

    /**
     * Check if allow to check payments
     *
     * @return mixed
     */
    public function isActive()
    {
        $return = $this->getConfigValue('is_active');
        return $return;
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

    public function getDobFieldTooltip()
    {
        return trim($this->getConfigValue('dob_tooltip'));
    }

    public function isDobFieldRequired()
    {
        return $this->getConfigFlag('is_dob_required');
    }

}
