<?php

namespace Leonex\RiskManagementPlatform\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class CheckingTime implements ArrayInterface
{
    const CHECKING_TIME_PRE = 'pre';
    const CHECKING_TIME_POST = 'post';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::CHECKING_TIME_PRE, 'label' => __('Before payment method selection')],
            ['value' => self::CHECKING_TIME_POST, 'label' => __('After payment method selection')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::CHECKING_TIME_PRE => __('Before payment method selection'),
            self::CHECKING_TIME_POST => __('After payment method selection')
        ];
    }
}
