<?php

namespace Leonex\RiskManagementPlatform\Setup;

use Leonex\RiskManagementPlatform\Setup\Patch\Data\PaymentMethodSelection;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Leonex\RiskManagementPlatform\Setup\UpgradeData
 */
class UpgradeData extends PaymentMethodSelection implements UpgradeDataInterface
{
    /**
     * {@inheritDoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->moduleDataSetup = $setup;
    }
}
