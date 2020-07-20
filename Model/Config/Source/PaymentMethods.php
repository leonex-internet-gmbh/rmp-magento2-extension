<?php

namespace Leonex\RiskManagementPlatform\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

/**
 * Leonex\RiskManagementPlatform\Model\Config\Source\PaymentMethods
 *
 * @author cstoller
 */
class PaymentMethods implements ArrayInterface
{
    protected $paymentHelper;

    public function __construct(PaymentHelper $paymentHelper)
    {
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->toArray() as $code => $name) {
            $options[] = [
                'value' => $code,
                'label' => $name . ' [' . $code . ']',
            ];
        }
        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->paymentHelper->getPaymentMethodList(true);
    }
}
