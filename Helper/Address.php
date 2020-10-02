<?php

namespace Leonex\RiskManagementPlatform\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Description of Address
 *
 * @author cstoller
 */
class Address extends AbstractHelper
{

    const XML_PATH = 'leonex_rmp/address/';

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
     * Get the mapping between prefixes and gender.
     *
     * @return array
     */
    public function getPrefixGenderMapping()
    {
        return $this->getConfigValue('prefix_gender_mapping');
    }

    /**
     * Map the passed prefix to a configured gender.
     *
     * @param string $prefix
     * @return string|null
     */
    public function mapPrefixToGender(string $prefix): ?string
    {
        $map = $this->getPrefixGenderMapping();
        foreach ($map as $mapping) {
            if (isset($mapping['prefix']) && $mapping['prefix'] === $prefix) {
                return $mapping['gender'] ?? null;
            }
        }
        return null;
    }
}
