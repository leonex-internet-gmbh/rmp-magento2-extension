<?php

namespace Leonex\RiskManagementPlatform\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    /** @var StoreManagerInterface */
    protected $_storeManager;

    /** @var ObjectManagerInterface */
    protected $_objectManager;

    /** @var State */
    protected $_state;

    const XML_PATH = 'leonex_rmp/settings/';

    /**
     * Data constructor.
     *
     * @param Context                $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface  $storeManager
     * @param State                  $state
     */
    public function __construct(
        Context $context, ObjectManagerInterface $objectManager, StoreManagerInterface $storeManager, State $state
    ) {
        $this->_objectManager = $objectManager;
        $this->_storeManager = $storeManager;
        $this->_state = $state;
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
    protected function _getConfigValue($code, $storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH . $code, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get value time of checking
     * pre | post
     *
     * @return mixed
     */
    public function getTimeOfChecking()
    {
        return $this->_getConfigValue('time_of_checking');
    }

    /**
     * Get api-url
     *
     * @return mixed
     */
    public function getApiUrl()
    {
        return $this->_getConfigValue('apiurl');
    }

    /**
     * Get api-key
     *
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->_getConfigValue('apikey');
    }

    /**
     * Check if allow to check payments
     *
     * @return mixed
     */
    public function isActive()
    {
        $return = $this->_getConfigValue('is_active');
        return $return;
    }

    /**
     * Check if admin context
     *
     * @return bool
     */
    public function isAdmin()
    {
        $return = 'adminhtml' === $this->_state->getAreaCode();
        return $return;
    }
}
