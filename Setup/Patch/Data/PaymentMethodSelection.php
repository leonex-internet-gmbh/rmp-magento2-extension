<?php

namespace Leonex\RiskManagementPlatform\Setup\Patch\Data;

use Leonex\RiskManagementPlatform\Helper\Data;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Description of PaymentMethodSelection
 *
 * @author cstoller
 */
class PaymentMethodSelection implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup, PaymentHelper $paymentHelper)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->paymentHelper = $paymentHelper;
    }

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
