<?php

namespace Leonex\RiskManagementPlatform\Model\ResourceModel\Log;

use Leonex\RiskManagementPlatform\Model\Log;
use Leonex\RiskManagementPlatform\Model\ResourceModel\Log as LogResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Leonex\RiskManagementPlatform\Model\ResourceModel\Log\Collection
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'log_id';
	protected $_eventPrefix = 'rmp_log_collection';
	protected $_eventObject = 'rmp_log_collection';

    protected $loadQuotesAfterLoad = false;
    protected $loadOrdersAfterLoad = false;

    protected $_quoteCollectionFactory;
    protected $_orderCollectionFactory;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->_quoteCollectionFactory = $quoteCollectionFactory;
        $this->_orderCollectionFactory = $orderCollectionFactory;
    }

    public function loadQuotesAfterLoad()
    {
        return $this->loadQuotesAfterLoad = true;
    }

    public function loadOrdersAfterLoad()
    {
        return $this->loadOrdersAfterLoad = true;
    }

    protected function _construct()
    {
        $this->_init(Log::class, LogResource::class);
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();

        if ($this->loadQuotesAfterLoad) {
            $logsByQuoteId = [];
            foreach ($this->getItems() as $item) {
                if ($item->getQuoteId()) {
                    $logsByQuoteId[$item->getQuoteId()] = $item;
                }
            }

            if ($logsByQuoteId) {
                /** @var \Magento\Quote\Model\ResourceModel\Quote\Collection $quoteCol */
                $quoteCol = $this->_quoteCollectionFactory->create();
                $quoteCol->addFieldToFilter('entity_id', ['in' => array_keys($logsByQuoteId)]);
                foreach ($quoteCol->getItems() as $quote) {
                    $logsByQuoteId[$quote->getId()]->setData('quote', $quote);
                }
            }
        }

        if ($this->loadOrdersAfterLoad) {
            $logsByOrderId = [];
            foreach ($this->getItems() as $item) {
                if ($item->getOrderId()) {
                    $logsByOrderId[$item->getOrderId()] = $item;
                }
            }

            if ($logsByOrderId) {
                /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $quoteCol */
                $orderCol = $this->_orderCollectionFactory->create();
                $orderCol->addFieldToFilter('entity_id', ['in' => array_keys($logsByOrderId)]);
                foreach ($orderCol->getItems() as $order) {
                    $logsByOrderId[$quote->getId()]->setData('order', $order);
                }
            }
        }

        return $this;
    }
}
