<?php

namespace Leonex\RiskManagementPlatform\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CheckingTime implements OptionSourceInterface
{
    const CHECKING_TIME_BEFORE_ORDER_PLACEMENT = 'before_order_placement';
    const CHECKING_TIME_PRE = 'pre';
    const CHECKING_TIME_POST = 'post';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::CHECKING_TIME_BEFORE_ORDER_PLACEMENT, 'label' => __('Before order placement')],
            ['value' => self::CHECKING_TIME_PRE, 'label' => __('Before payment method selection')],
            ['value' => self::CHECKING_TIME_POST, 'label' => __('After payment method selection')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::CHECKING_TIME_BEFORE_ORDER_PLACEMENT => __('Before order placement'),
            self::CHECKING_TIME_PRE => __('Before payment method selection'),
            self::CHECKING_TIME_POST => __('After payment method selection')
        ];
    }
}
