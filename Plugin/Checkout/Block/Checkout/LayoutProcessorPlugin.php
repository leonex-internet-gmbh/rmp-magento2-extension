<?php

namespace Leonex\RiskManagementPlatform\Plugin\Checkout\Block\Checkout;

use Leonex\RiskManagementPlatform\Helper\Data;

class LayoutProcessorPlugin
{
    /**
     * @var Data
     */
    protected $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }


    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, array $jsLayout)
    {
        $customAttributeCode = 'edob';
        $customField = [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                // customScope is used to group elements within a single form (e.g. they can be validated separately)
                'customScope' => 'shippingAddress.custom_attributes',
                'customEntry' => null,
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/date',

            ],
            'dataScope' => 'shippingAddress.custom_attributes' . '.' . $customAttributeCode,
            'label' => __('Date of Birth'),
            'provider' => 'checkoutProvider',
            'sortOrder' => 999,
            'validation' => [
                'required-entry' => $this->helper->isDobFieldRequired()
            ],
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
            'value' => '' // value field is used to set a default value of the attribute
        ];

        $toolTipHtml = $this->helper->getDobFieldTooltip();
        if ($toolTipHtml) {
            $customField['tooltip'] = [
                'description' => $toolTipHtml,
            ];
        }


        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$customAttributeCode] = $customField;

        return $jsLayout;
    }
}

