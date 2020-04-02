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
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Leonex\RiskManagementPlatform\Setup\UpgradeData
 */
class UpgradeData extends PaymentMethodSelection implements UpgradeDataInterface
{
    public function __construct(PaymentHelper $paymentHelper)
    {
        $this->paymentHelper = $paymentHelper;
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

        return;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '4.0.2', '<')) {
            $this->disableAllQuotes($installer);
        }
        if (version_compare($context->getVersion(), '5.3.2', '<')) {
            $this->disableInvalidQuotes($installer);
            $methods = [
                'klarna_pay_later',
                'klarna_pay_now',
                'klarna_pay_over_time',
                'klarna_direct_debit',
                'klarna_direct_bank_transfer'
            ];
            $methods = "'" . implode("','", $methods) . "'";

            $this->updateAdditionalInformation($installer, $methods);
            $this->changePaymentKeyToGeneric($installer, $methods);
        }
        if (version_compare($context->getVersion(), '5.4.5', '<')) {
            $this->removeStrongHtmlTag($installer);
        }
        if (version_compare($context->getVersion(), '5.5.4', '<')) {
            $this->clearDesignConfig($installer);
        }
        $installer->endSetup();
    }
}
