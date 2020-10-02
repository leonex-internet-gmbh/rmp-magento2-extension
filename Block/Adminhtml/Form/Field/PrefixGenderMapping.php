<?php

namespace Leonex\RiskManagementPlatform\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Leonex\RiskManagementPlatform\Block\Adminhtml\Form\Field\Gender;

/**
 * Leonex\RiskManagementPlatform\Block\Adminhtml\Form\Field\PrefixGenderMapping
 */
class PrefixGenderMapping extends AbstractFieldArray
{
    /**
     * @var TaxColumn
     */
    private $genderRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('prefix', ['label' => __('Prefix'), 'class' => 'required-entry']);
        $this->addColumn('gender', [
            'label' => __('Gender'),
            'renderer' => $this->getGenderRenderer()
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $gender = $row->getGender();
        if ($gender !== null) {
            $options['option_' . $this->getGenderRenderer()->calcOptionHash($gender)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return Gender
     * @throws LocalizedException
     */
    private function getGenderRenderer()
    {
        if (!$this->genderRenderer) {
            $this->genderRenderer = $this->getLayout()->createBlock(
                Gender::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->genderRenderer;
    }
}
