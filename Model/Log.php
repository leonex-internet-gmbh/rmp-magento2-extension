<?php

namespace Leonex\RiskManagementPlatform\Model;

use Leonex\RiskManagementPlatform\Model\ResourceModel\Log as LogResource;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\OrderRepository;

/**
 * Leonex\RiskManagementPlatform\Model\Log
 *
 * @method int|null getQuoteId()
 * @method int|null getOrderId()
 * @method string getLevel()
 * @method string getTag()
 * @method string getMessage()
 * @method array getPayload() Get additional unstructured data.
 * @method string getCreatedAt()
 * @method self setQuoteId(int $quoteId)
 * @method self setOrderId(int $orderId)
 * @method self setLevel(string $level)
 * @method self setTag(string $tag)
 * @method self setMessage(string $message)
 * @method self setPayload(array $value) Set additional unstructured data.
 * @method self setCreatedAt(string $createdAt)
 */
class Log extends AbstractModel implements IdentityInterface
{
	const CACHE_TAG = 'rmp_log';

	protected $_cacheTag = self::CACHE_TAG;

	protected $_eventPrefix = self::CACHE_TAG;

    protected $_eventObject = self::CACHE_TAG;

    protected $quoteRepository;

    protected $orderRepository;

    public function __construct(
        QuoteRepository $quoteRepository,
        OrderRepository $orderRepository,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = array()
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
    }

    	protected function _construct()
	{
		$this->_init(LogResource::class);
	}

	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}

    public function getQuote(): ?\Magento\Quote\Model\Quote
    {
        if (!$this->getQuoteId()) {
            return null;
        }

        if (!$this->getData('quote') || $this->getData('quote')->getId() != $this->getQuoteId()) {
            $quote = $this->quoteRepository->get($this->getQuoteId());
            $this->setData('quote', $quote);
        }

        return $this->getData('quote');
    }

    public function getOrder(): ?\Magento\Sales\Model\Order
    {
        if (!$this->getOrderId()) {
            return null;
        }

        if (!$this->getData('order') || $this->getData('order')->getId() != $this->getOrderId()) {
            $order = $this->orderRepository->get($this->getOrderId());
            $this->setData('order', $order);
        }

        return $this->getData('order');
    }
}
