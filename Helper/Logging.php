<?php

namespace Leonex\RiskManagementPlatform\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Leonex\RiskManagementPlatform\Helper\Logging
 */
class Logging extends AbstractHelper
{
    const XML_PATH = 'leonex_rmp/logging/';

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

    /**
     * Check if debug logging is enabled.
     *
     * @return bool
     */
    public function isDebugLoggingEnabled(): bool
    {
        return (bool) $this->getConfigFlag('debug_logging_enabled');
    }
}
