<?php

namespace Leonex\RiskManagementPlatform\Setup\Patch\Data;

use Leonex\RiskManagementPlatform\Helper\Data;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\State;
use Magento\Framework\Session\SessionStartChecker;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface; // since Magento 2.3

if (interface_exists('Magento\Framework\Setup\Patch\DataPatchInterface', false)) {
    class PaymentMethodSelection implements DataPatchInterface
    {
        protected $paymentHelper;
        protected $moduleDataSetup;
        protected $sessionStartChecker;
        protected $appState;

        public function __construct(
            ModuleDataSetupInterface $moduleDataSetup,
            PaymentHelper $paymentHelper,
            SessionStartChecker $sessionStartChecker,
            State $appState
        ) {
            $this->paymentHelper = $paymentHelper;
            $this->moduleDataSetup = $moduleDataSetup;
            $this->sessionStartChecker = $sessionStartChecker;
            $this->appState = $appState;
        }

        /**
         * {@inheritdoc}
         */
        public function apply()
        {
            if (!isset($this->sessionStartChecker) || $this->sessionStartChecker->check()) {
                $this->appState->setAreaCode('global');
            }

            $this->moduleDataSetup->getConnection()->startSetup();

            $configTable = $this->moduleDataSetup->getTable('core_config_data');

            $con = $this->moduleDataSetup->getConnection();
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

            $this->moduleDataSetup->getConnection()->endSetup();
        }

        /**
         * {@inheritdoc}
         */
        public static function getDependencies()
        {
            return [];
        }

        /**
         * {@inheritdoc}
         */
        public function getAliases()
        {
            return [];
        }
    }
} else {
    class PaymentMethodSelection {}
}