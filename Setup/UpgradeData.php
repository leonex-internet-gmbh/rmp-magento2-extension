<?php

namespace Leonex\RiskManagementPlatform\Setup;

use Leonex\RiskManagementPlatform\Helper\Data;
use Leonex\RiskManagementPlatform\Helper\Logging;
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
    protected $sessionStartChecker;
    protected $appState;

    protected $initialConfig;

    public function __construct(
        \Magento\Framework\App\Config\Initial $initialConfig,
        SessionStartChecker $sessionStartChecker,
        State $appState
    ) {
        $this->initialConfig = $initialConfig;
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

        if (version_compare($context->getVersion(), '2.0.2', '<')) {
            $this->setupLoggingCapabilities($setup);
        }

        if (version_compare($context->getVersion(), '2.2.0', '<')) {
            $this->addPrefixGenderMappingDefaults($setup);
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

        $paymentMethodsImploded = implode(',', array_keys($this->getPaymentMethods()));

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

    private function setupLoggingCapabilities(ModuleDataSetupInterface $setup): void
    {
        $configTable = $setup->getTable('core_config_data');

        $setup->getConnection()->update(
            $configTable,
            ['path' => Logging::XML_PATH . 'debug_logging_enabled'],
            ['path = ?' => Data::XML_PATH . 'debug_logging_enabled']
        );

    }

    private function addPrefixGenderMappingDefaults(ModuleDataSetupInterface $setup): void
    {
        $configTable = $setup->getTable('core_config_data');

        // We have to insert the defaults, defined in the config.xml, into the database
        // as a workaround for https://github.com/magento/magento2/issues/30314
        $setup->getConnection()->insert($configTable, [
            'scope' => 'default',
            'scope_id' => 0,
            'path' => 'leonex_rmp/address/prefix_gender_mapping',
            'value' => json_encode([
                'item1' => ['prefix' => 'Herr', 'gender' => 'm'],
                'item2' => ['prefix' => 'Frau', 'gender' => 'f'],
                'item3' => ['prefix' => 'Divers', 'gender' => 'd'],
            ]),
        ]);

        $setup->getConnection()->update(
            $configTable,
            ['path' => 'leonex_rmp/address/dob_tooltip'],
            ['path = ?' => 'leonex_rmp/settings/dob_tooltip']
        );

        $setup->getConnection()->update(
            $configTable,
            ['path' => 'leonex_rmp/address/is_dob_required'],
            ['path = ?' => 'leonex_rmp/settings/is_dob_required']
        );
    }

    protected function getPaymentMethods()
    {
        return $this->initialConfig->getData('default')[PaymentHelper::XML_PATH_PAYMENT_METHODS];
    }
}
