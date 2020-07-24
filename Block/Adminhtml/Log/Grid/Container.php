<?php

namespace Leonex\RiskManagementPlatform\Block\Adminhtml\Log\Grid;

use Magento\Backend\Block\Widget\Grid\Container as BaseContainer;

/**
 * Leonex\RiskManagementPlatform\Block\Adminhtml\Log\Grid\Container
 */
class Container extends BaseContainer
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_log';
        $this->_blockGroup = 'Leonex_RiskManagementPlatform';
        $this->_headerText = __('Logs');

        parent::_construct();
    }

    protected function _prepareLayout()
    {
        $this->buttonList->remove('add');

        return parent::_prepareLayout();
    }


}
