<?php


namespace Leonex\RiskManagementPlatform\Ui\DataProvider\Log\Listing;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Leonex\RiskManagementPlatform\Ui\DataProvider\Log\Listing\Collection
 */
class Collection extends SearchResult
{
    /**
     * Override _initSelect to add custom columns
     *
     * @return void
     */
    protected function _initSelect()
    {
        $this->addFilterToMap('log_id', 'main_table.log_id');
        $this->addFilterToMap('quote_id', 'main_table.quote_id');
        $this->addFilterToMap('order_increment_id', 'order.increment_id');
        $this->addFilterToMap('level', 'main_table.level');
        $this->addFilterToMap('tag', 'main_table.tag');
        $this->addFilterToMap('message', 'main_table.message');
        $this->addFilterToMap('created_at', 'main_table.created_at');

        $select = $this->getSelect();
        $select->joinLeft('quote', 'quote.entity_id = main_table.quote_id', []);
        $select->joinLeft(['quote_address' => 'quote_address'], "quote_address.quote_id = main_table.quote_id AND quote_address.address_type = 'billing'", []);
        $select->joinLeft(['order' => 'sales_order'], 'order.entity_id = main_table.order_id', []);
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

        parent::_initSelect();
    }

    /**
     * {@inheritDoc}
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ('billing_name' === $field) {
            $select = $this->getSelect();
            $cond = "CONCAT(IFNULL(quote_address.firstname, ''), ' ', IFNULL(quote_address.lastname, ''), '||',
                IFNULL(order.customer_firstname, ''), ' ', IFNULL(order.customer_lastname, '')) LIKE ?";
            $value = current($condition);
            $select->where($cond, "%$value%");
        } else {
            parent::addFieldToFilter($field, $condition);
        }
    }
}
