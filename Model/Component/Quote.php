<?php

namespace Leonex\RiskManagementPlatform\Model\Component;

use Leonex\RiskManagementPlatform\Helper\Address;
use Leonex\RiskManagementPlatform\Helper\CheckoutStatus;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;

class Quote
{
    /**
     * Mapping of Magento gender values to RMP gender values.
     */
    const GENDER_MAPPING = [
        1 => 'm',
        2 => 'f',
        3 => 'd',
    ];

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quote;

    /**
     * @var array
     */
    protected $normalizedQuote;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface
     */
    protected $customer;

    /**
     * @var CollectionFactoryInterface
     */
    protected $orderFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Address
     */
    protected $addressHelper;

    /**
     * @var CheckoutStatus
     */
    protected $checkoutStatus;

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
        Address $addressHelper,
        CheckoutStatus $checkoutStatus,
        CollectionFactoryInterface $orderFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->addressHelper = $addressHelper;
        $this->checkoutStatus = $checkoutStatus;
        $this->quote = $this->checkoutSession->getQuote();
        $this->customer = $this->quote->getCustomer();
        $this->orderFactory = $orderFactory;
    }

    /**
     * Check whether a billing address has been provided.
     * @return bool
     */
    public function isAddressProvided(): bool
    {
        $billingAddress = $this->quote->getBillingAddress();

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
            'shippingAddress' => $this->getShippingAddress(), // needed for cache invalidation and comparison in platform
            'quote' => [
                'items' => $this->getQuoteItems(),
                'totalAmount' => (float) $this->quote->getGrandTotal(),
            ],
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
        if ($this->checkoutStatus->hasBillingAddressReallyBeenSet()) {
            $billingAddress = $this->quote->getBillingAddress();
        } else {
            $billingAddress = $this->quote->getShippingAddress();
        }

        return [
            'gender' => $this->getGender($billingAddress),
            'lastName' => $billingAddress->getLastname(),
            'firstName' => $billingAddress->getFirstname(),
            'dateOfBirth' => substr($this->quote->getCustomerDob(), 0, 10), // ?
            'birthName' => '',
            'street' => $billingAddress->getStreetFull(),
            'zip' => $billingAddress->getPostcode(),
            'city' => $billingAddress->getCity(),
            'country' => strtolower($billingAddress->getCountryId()),
        ];
    }

    /**
     * Adjust the data from the shipping address.
     *
     * @return array
     */
    protected function getShippingAddress(): array
    {
        $shippingAddress = $this->quote->getShippingAddress();

        return [
            'gender' => $this->getGender($shippingAddress),
            'lastName' => $shippingAddress->getLastname(),
            'firstName' => $shippingAddress->getFirstname(),
            'dateOfBirth' => substr($this->quote->getCustomerDob(), 0, 10), // ?
            'birthName' => '',
            'street' => $shippingAddress->getStreetFull(),
            'zip' => $shippingAddress->getPostcode(),
            'city' => $shippingAddress->getCity(),
            'country' => strtolower($shippingAddress->getCountryId()),
        ];
    }

    /**
     * Get the ID of the quote model.
     *
     * @return int
     */
    public function getQuoteId(): int
    {
        return $this->quote->getId();
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

        if ($email = $this->quote->getBillingAddress()->getEmail()) {
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

    /**
     * Try to extract the gender from a quote address. If this is not possible
     * a fallback is done to customer gender or prefix.
     *
     * @param AddressInterface $address
     * @return string|null
     */
    protected function getGender(AddressInterface $address): ?string
    {
        if ($address->getPrefix()) {
            $gender = $this->addressHelper->mapPrefixToGender($address->getPrefix());
            if ($gender) {
                return $gender;
            }
        }

        $gender = $this->quote->getCustomerGender() ?: $this->customer->getGender();
        $gender = self::GENDER_MAPPING[$gender] ?? null;
        if ($gender) {
            return $gender;
        }

        $prefix = $this->quote->getCustomerPrefix() ?: $this->customer->getPrefix();
        return $this->addressHelper->mapPrefixToGender((string) $prefix);
    }
}
