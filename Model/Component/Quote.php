<?php

namespace Leonex\RiskManagementPlatform\Model\Component;

use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;

class Quote
{
    /** @var \Magento\Quote\Model\Quote */
    protected $_quote;

    /** @var array */
    protected $_normalizedQuote;

    /** @var \Magento\Quote\Model\Quote\Address */
    protected $_billingAddress;

    /** @var \Magento\Customer\Api\Data\CustomerInterface */
    protected $_customer;

    /** @var CollectionFactoryInterface */
    protected $_orderFactory;

    const GENDER
        = array(
            1 => 'm', 2 => 'f'
        );

    /**
     * Quote constructor.
     *
     * @param Session                    $checkoutSession
     * @param CollectionFactoryInterface $orderFactory
     */
    public function __construct(
        Session $checkoutSession, CollectionFactoryInterface $orderFactory
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_quote = $this->_checkoutSession->getQuote();
        $this->_billingAddress = $this->_quote->getBillingAddress();
        $this->_customer = $this->_quote->getCustomer();
        $this->_orderFactory = $orderFactory;
    }

    /**
     * Return the normalized quote and trigger a filter event.
     *
     * @return mixed
     */
    public function getNormalizedQuote()
    {
        return $this->_normalizeQuote();
    }

    /**
     * Structure the given information in a new structured way.
     * The structure correlate with required api-structure.
     *
     * @return array
     */
    protected function _normalizeQuote()
    {
        return ([
            'customerSessionId' => $this->_getSessionID(), 'justifiableInterest' => Connector::JUSTIFIABLE_INTEREST_BUSINESS_INITIATION, 'consentClause' => true, 'billingAddress' => $this->_getBillingAddress(), 'quote' => $this->_getQuote(), 'customer' => $this->_getCustomerData(), 'orderHistory' => $this->_getOrderHistory()
        ]);
    }

    /**
     * Return the user session identifier.
     *
     * @return mixed
     */
    protected function _getSessionID()
    {
        return $this->_checkoutSession->getSessionId();
    }

    /**
     * Adjust the data from the billing address.
     *
     * @return array
     */
    protected function _getBillingAddress()
    {
        $billingAddress = $this->_billingAddress;
        return [
            'gender' => self::GENDER[$this->_customer->getGender()], 'lastName' => $billingAddress->getLastname(), 'firstName' => $billingAddress->getFirstname(), 'dateOfBirth' => $this->_customer->getDob(), 'birthName' => '', 'street' => $this->_getFirstStreet(), 'street2' => $this->_getSecondStreet(), 'zip' => $billingAddress->getPostcode(), 'city' => $billingAddress->getCity(), 'country' => $billingAddress->getCountryId(),
        ];
    }

    /**
     * Get the item quote.
     * Includes the total amount and a array of basket items.
     *
     * @return array
     */
    protected function _getQuote()
    {
        return array(
            'items' => $this->_getQuoteItems(), 'totalAmount' => $this->_quote->getGrandTotal(),
        );
    }

    /**
     * Get the items from basket as array.
     *
     * @return array
     */
    protected function _getQuoteItems()
    {
        $quoteItems = array();

        /** @var Item $item */
        foreach ($this->_quote->getAllItems() as $item) {
            if (is_null($item->getParentItemId())) {
                $quoteItems[] = array(
                    'sku' => $item->getSku(), 'quantity' => $item->getQty(), 'price' => (float)$item->getPriceInclTax(), 'rowTotal' => (float)$item->getRowTotal()
                );
            }
        }
        return $quoteItems;
    }

    /**
     * Get the number and email from the customer.
     *
     * @return array
     */
    protected function _getCustomerData()
    {
        $customer = $this->_customer;
        return array(
            'number' => $customer->getId(), 'email' => $customer->getEmail()
        );
    }

    /**
     * Get the customer history from the quote model.
     *
     * @return array
     */
    protected function _getOrderHistory()
    {
        return array(
            'numberOfCanceledOrders' => $this->_getNumberOfCanceledOrders(), 'numberOfCompletedOrders' => $this->_getNumberOfCompletedOrders(), 'numberOfUnpaidOrders' => $this->_getNumberOfUnpaidOrders(), 'numberOfOutstandingOrders' => $this->_getNumberOfOutstandingOrders(),
        );
    }

    /**
     * Get the first Street from billing address
     *
     * @return mixed
     */
    protected function _getFirstStreet()
    {
        return $this->_billingAddress->getStreet()[0];
    }

    /**
     * Get the secondary Street from billing address
     *
     * @return string
     */
    protected function _getSecondStreet()
    {
        if (count($this->_billingAddress->getStreet()) > 1) {
            return $this->_billingAddress->getStreet()[1];
        }
        return '';
    }

    /**
     * Create a md5 from the basket and customer to block recurring events.
     *
     * @return string
     */
    public function getQuoteHash()
    {
        $billingAddress = $this->_billingAddress;

        $hash = $this->_getSessionID();
        $hash .= $billingAddress->getLastname();
        $hash .= $billingAddress->getFirstname();
        $hash .= $this->_getFirstStreet();
        $hash .= $billingAddress->getPostcode();
        $hash .= $billingAddress->getCity();

        /** @var Item $item */
        foreach ($this->_quote->getAllItems() as $item) {
            if (is_null($item->getParentItemId())) {
                $hash .= $item->getSku();
                $hash .= $item->getQty();
            }
        }

        return hash('sha256', $hash);
    }

    /**
     * compare a given md5 with a new generated from the quote.
     *
     * @param $hash
     *
     * @return bool
     */
    public function hashCompare($hash)
    {
        $return = $hash == $this->getQuoteHash();
        return $return;
    }

    /**
     * Get the number of canceled orders
     *
     * @return int
     */
    protected function _getNumberOfCanceledOrders()
    {
        return $this->_getNumberOf([Order::STATE_CANCELED]);
    }

    /**
     * Get the number of completed orders
     *
     * @return int
     */
    protected function _getNumberOfCompletedOrders()
    {
        return $this->_getNumberOf([Order::STATE_COMPLETE]);
    }

    /**
     * Get the number of unpaid orders
     *
     * @return int
     */
    protected function _getNumberOfUnpaidOrders()
    {
        return $this->_getNumberOf([Order::STATE_PENDING_PAYMENT]);
    }

    /**
     * Get the number of outstanding orders
     *
     * @return int
     */
    protected function _getNumberOfOutstandingOrders()
    {
        return $this->_getNumberOf([
                Order::STATE_PENDING_PAYMENT, Order::STATE_NEW, Order::STATE_HOLDED, Order::STATE_PROCESSING
            ]);
    }

    /**
     * Get The Number of orders by given state
     *
     * @param array $states
     *
     * @return int
     */
    protected function _getNumberOf(array $states)
    {
        return $this->_orderFactory->create($this->_customer->getId())->addFieldToSelect('entity_id')->addFieldToFilter('state', $states)->count();
    }
}
