<?php

namespace Leonex\RiskManagementPlatform\Block\Adminhtml\Log;

use Magento\Backend\Block\Widget\Grid as BaseGrid;

/**
 * Leonex\RiskManagementPlatform\Block\Adminhtml\Log\Grid
 *
 * @author cstoller
 */
class Grid extends BaseGrid
{
    protected function _prepareCollection()
    {
        parent::_prepareCollection();

        /** @var \Leonex\RiskManagementPlatform\Model\ResourceModel\Log\Collection $col */
        if ($col = $this->getCollection()) {
            $select = $col->getSelect();
            $select->joinLeft('quote', 'quote.entity_id = main_table.quote_id');
            $select->joinLeft(['quote_address' => 'quote_address'], "quote_address.quote_id = main_table.quote_id AND quote_address.address_type = 'billing'");
            $select->joinLeft(['order' => 'sales_order'], 'order.entity_id = main_table.order_id');
            $select->columns([
                'order_increment_id' => 'order.increment_id',
                'billing_name' => "IF(
                    order.entity_id IS NOT NULL,
                    CONCAT(order.customer_firstname, ' ', order.customer_lastname),
                    IF(
                        quote_address.address_id IS NOT NULL,
                        CONCAT(quote_address.firstname, ' ', quote_address.lastname),
                        NULL
                    ))",
            ]);
        }

        return $this;
    }

}
