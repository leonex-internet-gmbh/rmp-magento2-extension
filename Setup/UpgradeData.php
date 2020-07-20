<?php

namespace Leonex\RiskManagementPlatform\Setup;

use Leonex\RiskManagementPlatform\Helper\Data;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\State;
use Magento\Framework\Session\SessionStartChecker;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Leonex\RiskManagementPlatform\Setup\UpgradeData
 */
class UpgradeData implements UpgradeDataInterface
{
    protected $paymentHelper;
    protected $sessionStartChecker;
    protected $appState;

    public function __construct(
        PaymentHelper $paymentHelper,
        SessionStartChecker $sessionStartChecker,
        State $appState
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->sessionStartChecker = $sessionStartChecker;
        $this->appState = $appState;
    }

    /**
     * {@inheritDoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (!isset($this->sessionStartChecker) || $this->sessionStartChecker->check()) {
            $this->appState->setAreaCode('global');
        }

        $setup->startSetup();

        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            $this->selectPaymentsToCheck($setup);
        }

        $setup->endSetup();
    }

    private function selectPaymentsToCheck(ModuleDataSetupInterface $setup): void
    {
        $configTable = $setup->getTable('core_config_data');

        $con = $setup->getConnection();
        $stm = $con->select()
            ->from($configTable)
            ->where('path = ?', Data::XML_PATH . 'is_active')
            ->where('value = 1')
            ->query();
        $activeRows = $stm->fetchAll();

        $stm = $con->select()
            ->from($configTable)
            ->where('path = ?', Data::XML_PATH . 'payment_methods_to_check')
            ->query();
        $methodSelectionRows = $stm->fetchAll();

        $paymentMethodsImploded = implode(',', array_keys($this->paymentHelper->getPaymentMethodList(true)));

        // check if the extension is enabled already
        if (count($activeRows) > 0 && empty($methodSelectionRows)) {
            foreach ($activeRows as $acriveRow) {
                $con->insert($configTable, [
                    'scope' => $acriveRow['scope'],
                    'scope_id' => $acriveRow['scope_id'],
                    'path' => Data::XML_PATH . 'payment_methods_to_check',
                    'value' => $paymentMethodsImploded,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
