<?php

namespace Leonex\RiskManagementPlatform\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

/**
 * Leonex\RiskManagementPlatform\Block\Adminhtml\Form\Field\Gender
 */
class Gender extends Select
{
    const OPTION_LABELS = [
        'm' => 'male',
        'f' => 'female',
        'd' => 'diverse',
    ];

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    private function getSourceOptions(): array
    {
        $options = [['label' => __('unknown'), 'value' => null]];

        foreach (self::OPTION_LABELS as $value => $label) {
            $options[] = ['label' => __($label), 'value' => $value];
        }

        return $options;
    }
}
