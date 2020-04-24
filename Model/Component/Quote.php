<?php

namespace Leonex\RiskManagementPlatform\Model\Component;

use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;

class Quote
{
    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quote;

    /**
     * @var array
     */
    protected $normalizedQuote;

    /**
     * @var \Magento\Quote\Model\Quote\Address
     */
    protected $billingAddress;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface
     */
    protected $customer;

    /**
     * @var CollectionFactoryInterface
     */
    protected $orderFactory;

    const GENDER = [
        1 => 'm',
        2 => 'f'
    ];
    /**
     * @var \Magento\Quote\Model\Quote\Address
     */
    protected $shippingAddress;
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * Quote constructor.
     *
     * @param Session                    $checkoutSession
     * @param CollectionFactoryInterface $orderFactory
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        Session $checkoutSession,
        CollectionFactoryInterface $orderFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quote = $this->checkoutSession->getQuote();
        $this->billingAddress = $this->quote->getBillingAddress();
        $this->shippingAddress = $this->quote->getShippingAddress();
        $this->customer = $this->quote->getCustomer();
        $this->orderFactory = $orderFactory;
    }

    /**
     * Check whether a billing address has been provided
     * @return bool
     */
    public function isAddressProvided(): bool
    {
        $billingAddress = $this->billingAddress;

        return $billingAddress->getCompany()
            || $billingAddress->getLastname()
            || $billingAddress->getFirstname();
    }

    /**
     * Get the quote's grand total.
     *
     * @return float
     */
    public function getGrandTotal(): float
    {
        return (float) $this->quote->getGrandTotal();
    }

    /**
     * Structure the given information in a new structured way.
     * The structure correlate with required api-structure.
     *
     * @return array
     */
    public function getNormalizedQuote(): array
    {
        return [
            'customerSessionId' => $this->quote->getId(),
            'justifiableInterest' => Connector::JUSTIFIABLE_INTEREST_BUSINESS_INITIATION,
            'consentClause' => true,
            'billingAddress' => $this->getBillingAddress(),
            'quote' => $this->getQuote(),
            'customer' => $this->getCustomerData(),
            'orderHistory' => $this->getOrderHistory()
        ];
    }

    /**
     * Adjust the data from the billing address.
     *
     * @return array
     */
    protected function getBillingAddress(): array
    {
        $billingAddress = $this->billingAddress;
        $gender = array_key_exists($this->customer->getGender(), self::GENDER) ? self::GENDER[$this->customer->getGender()] : null;

        return [
            'gender' => $gender,
            'lastName' => $billingAddress->getLastname(),
            'firstName' => $billingAddress->getFirstname(),
            'dateOfBirth' => substr($this->quote->getCustomerDob(), 0, 10), // ?
            'birthName' => '',
            'street' => $this->getStreet(),
            'zip' => $billingAddress->getPostcode(),
            'city' => $billingAddress->getCity(),
            'country' => strtolower($billingAddress->getCountryId()),
        ];
    }

    /**
     * Get the item quote.
     * Includes the total amount and a array of basket items.
     *
     * @return array
     */
    protected function getQuote(): array
    {
        return [
            'items' => $this->getQuoteItems(),
            'totalAmount' => (float) $this->quote->getGrandTotal(),
        ];
    }

    /**
     * Get the items from basket as array.
     *
     * @return array
     */
    protected function getQuoteItems(): array
    {
        $quoteItems = [];

        /** @var Item $item */
        foreach ($this->quote->getAllItems() as $item) {
            if (is_null($item->getParentItemId())) {
                $quoteItems[] = [
                    'sku' => $item->getSku(),
                    'quantity' => $item->getQty(),
                    'price' => (float) $item->getPriceInclTax(),
                    'rowTotal' => (float) $item->getRowTotal()
                ];
            }
        }
        return $quoteItems;
    }

    /**
     * Get the number and email from the customer.
     *
     * @return array
     */
    protected function getCustomerData(): array
    {
        return [
            'number' => $this->customer->getId(),
            'email' => $this->getCustomerEmail(),
        ];
    }

    /**
     * Get the customer's email address.
     *
     * @return string|null
     */
    public function getCustomerEmail(): ?string
    {
        if ($email = $this->quote->getCustomerEmail()) {
            return $email;
        }

        if ($email = $this->billingAddress->getEmail()) {
            return $email;
        }

        $email = $this->checkoutSession->getStepData('billing_address', 'leonex.rmp.email');
        return $email ?: null;
    }

    /**
     * Get the customer history from the quote model.
     *
     * @return array
     */
    protected function getOrderHistory(): array
    {
        return [
            'numberOfCanceledOrders' => $this->getNumberOfCanceledOrders(),
            'numberOfCompletedOrders' => $this->getNumberOfCompletedOrders(),
            'numberOfUnpaidOrders' => $this->getNumberOfUnpaidOrders(),
        ];
    }

    /**
     * Get the first Street from billing address
     *
     * @return mixed
     */
    protected function getStreet()
    {
        return $this->billingAddress->getStreetFull();
    }

    /**
     * Create a md5 from the basket and customer to block recurring events.
     *
     * @return string
     */
    public function getQuoteHash(): string
    {
        $json = \json_encode($this->getNormalizedQuote());
        return hash('sha256', $json);
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
    protected function getNumberOfCanceledOrders()
    {
        return $this->getNumberOf([Order::STATE_CANCELED]);
    }

    /**
     * Get the number of completed orders
     *
     * @return int
     */
    protected function getNumberOfCompletedOrders()
    {
        return $this->getNumberOf([Order::STATE_COMPLETE]);
    }

    /**
     * Get the number of unpaid orders
     *
     * @return int
     */
    protected function getNumberOfUnpaidOrders(): int
    {
        return $this->getNumberOf([
            Order::STATE_PENDING_PAYMENT,
            Order::STATE_NEW,
            Order::STATE_HOLDED,
            Order::STATE_PROCESSING
        ]);
    }

    /**
     * Get The Number of orders by given state
     *
     * @param array $states
     *
     * @return int
     */
    protected function getNumberOf(array $states): int
    {
        $col = $this->orderFactory->create($this->customer->getId());
        $col->addFieldToSelect('entity_id');
        $col->addFieldToFilter('state', $states);

        if (!$this->customer->getId()) {
            $email = $this->getCustomerEmail();
            $col->addFieldToFilter('customer_email', ['like' => $email]);
        }

        return $col->count();
    }
}
