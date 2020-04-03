<?php
/**
 * This file is part of the Klarna Kp module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace Leonex\RiskManagementPlatform\Setup;

use Leonex\RiskManagementPlatform\Setup\Patch\Data\PaymentMethodSelection;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\State;
use Magento\Framework\Session\SessionStartChecker;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Leonex\RiskManagementPlatform\Setup\UpgradeData
 */
class UpgradeData extends PaymentMethodSelection implements UpgradeDataInterface
{
    public function __construct(PaymentHelper $paymentHelper, SessionStartChecker $sessionStartChecker, State $appState)
    {
        $this->paymentHelper = $paymentHelper;
        $this->sessionStartChecker = $sessionStartChecker;
        $this->appState = $appState;
    }

    /**
     * {@inheritDoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->moduleDataSetup = $setup;

        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            $this->apply();
        }
    }
}
