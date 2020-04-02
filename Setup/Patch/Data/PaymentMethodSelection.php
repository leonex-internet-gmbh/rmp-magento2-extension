<?php

namespace Leonex\RiskManagementPlatform\Setup\Patch\Data;

use Leonex\RiskManagementPlatform\Helper\Data;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface; // since Magento 2.3


if (interface_exists('Magento\Framework\Setup\Patch\DataPatchInterface', false)) {
    /**
     * Data upgrade in Magento 2.3
     */
    abstract class PaymentMethodSelectionAbstract implements DataPatchInterface
    {
        public function __construct(ModuleDataSetupInterface $moduleDataSetup, PaymentHelper $paymentHelper)
        {
            parent::__construct($paymentHelper);
            $this->moduleDataSetup = $moduleDataSetup;
        }
    }
} else if (interface_exists('Magento\Framework\Setup\UpgradeDataInterface', false)) {
    /**
     * Data upgrade in Magento 2.2
     */
    abstract class PaymentMethodSelectionAbstract implements \Magento\Framework\Setup\UpgradeDataInterface
    {
        public function upgrade(ModuleDataSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
        {
            // Dummy method. Please look at Leonex\RiskManagementPlatform\Setup\UpgradeData
        }
    }
}


class PaymentMethodSelection extends PaymentMethodSelectionAbstract
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
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
