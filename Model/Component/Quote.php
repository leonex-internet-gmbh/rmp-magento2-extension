<?php

namespace Leonex\RiskManagementPlatform\Model\Component;

use Leonex\RiskManagementPlatform\Helper\Address;
use Leonex\RiskManagementPlatform\Helper\CheckoutStatus;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\Address as AddressModel;
use Magento\Quote\Model\Quote as QuoteModel;
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
     *
     * @deprecad since 2.3.0 - use \Leonex\RiskManagementPlatform\Helper\CheckoutStatus::isAddressProvided($quote) instead.
     */
    public function isAddressProvided(): bool
    {
        trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Use \Leonex\RiskManagementPlatform\Helper\CheckoutStatus::isAddressProvided($quote) instead.');

        return $this->checkoutStatus->isAddressProvided($this->quote);
    }

    /**
     * Get the quote's grand total.
     *
     * @deprecated since 2.3.0, use the getGrandTotal of the quote model directly.
     * @return float
     */
    public function getGrandTotal(): float
    {
        trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Use the getGrandTotal of the quote model directly.');

        return (float) $this->quote->getGrandTotal();
    }

    /**
     * Structure the given information in a new structured way.
     * The structure correlate with required api-structure.
     *
     * @return array
     */
    public function getNormalizedQuote(?QuoteModel $quote = null): array
    {
        if (!$quote) {
            trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Calling the getNormalizedQuote method of the quote component model without passing the quote is deprecated.');
            $quote = $this->quote;
        }

        return [
            'customerSessionId' => $quote->getId(),
            'justifiableInterest' => Connector::JUSTIFIABLE_INTEREST_BUSINESS_INITIATION,
            'consentClause' => true,
            'billingAddress' => $this->normalizeBillingAddress($quote),
            'shippingAddress' => $this->normalizeShippingAddress($quote), // needed for cache invalidation and comparison in platform
            'quote' => [
                'items' => $this->getQuoteItems($quote),
                'totalAmount' => (float) $quote->getGrandTotal(),
            ],
            'customer' => $this->getCustomerData($quote),
            'orderHistory' => $this->getOrderHistory($quote)
        ];
    }

    /**
     * Normalize the data from the billing address.
     */
    protected function normalizeBillingAddress(QuoteModel $quote): ?array
    {
        // In Magento's checkout the shipping address is inquired first, but those data is stored in the billing address, too.
        // But in the RMP this behaviour has some bad impact on the scoring. This is why we'll only send null, here.
        if (!$this->checkoutStatus->hasBillingAddressReallyBeenSet($quote)) {
            return null;
        }

        $billingAddress = $quote->getBillingAddress();
        $dob = $quote->getCustomerDob();

        return [
            'gender' => $this->getGender($billingAddress),
            'lastName' => $billingAddress->getLastname(),
            'firstName' => $billingAddress->getFirstname(),
            'dateOfBirth' => $dob ? substr($dob, 0, 10) : null,
            'birthName' => '',
            'street' => $billingAddress->getStreetFull(),
            'zip' => $billingAddress->getPostcode(),
            'city' => $billingAddress->getCity(),
            'country' => strtolower($billingAddress->getCountryId()),
        ];
    }

    /**
     * Normalize the data from the shipping address.
     */
    protected function normalizeShippingAddress(QuoteModel $quote): array
    {
        $shippingAddress = $quote->getShippingAddress();
        $dob = $quote->getCustomerDob();

        return [
            'gender' => $this->getGender($shippingAddress),
            'lastName' => $shippingAddress->getLastname(),
            'firstName' => $shippingAddress->getFirstname(),
            'dateOfBirth' => $dob ? substr($dob, 0, 10) : null,
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
     * @deprecated since 2.3.0
     */
    public function getQuoteId(): int
    {
        trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Calling the getQuoteId() method of the quote component model is deprecated.');
        return $this->quote->getId();
    }

    /**
     * @deprecated since 2.3.0
     */
    public function getQuote(): ?QuoteModel
    {
        trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Calling the getQuote method of the quote component model is deprecated.');
        return $this->quote;
    }

    /**
     * Get the items from basket as array.
     *
     * @return array
     */
    protected function getQuoteItems(?QuoteModel $quote = null): array
    {
        if (!$quote) {
            trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Calling the getQuoteItems method of the quote component model without passing the quote is deprecated.');
            $quote = $this->quote;
        }

        $quoteItems = [];

        /** @var Item $item */
        foreach ($quote->getAllItems() as $item) {
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
    protected function getCustomerData(?QuoteModel $quote = null): array
    {
        if (!$quote) {
            trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Calling the getCustomerData method of the quote component model without passing the quote is deprecated.');
            $quote = $this->quote;
        }

        return [
            'number' => $quote->getCustomer()->getId(),
            'email' => $this->getCustomerEmail($quote),
        ];
    }

    /**
     * Get the customer's email address.
     *
     * @return string|null
     */
    public function getCustomerEmail(?QuoteModel $quote = null): ?string
    {
        if (!$quote) {
            trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Calling the getCustomerData method of the quote component model without passing the quote is deprecated.');
            $quote = $this->quote;
        }

        if ($email = $quote->getBillingAddress()->getEmail()) {
            return $email;
        }

        if ($email = $quote->getCustomerEmail()) {
            return $email;
        }

        $email = $this->checkoutSession->getStepData('shipping_address', 'leonex.rmp.email');
        return $email ?: null;
    }

    /**
     * Get the customer history from the quote model.
     *
     * @return array
     */
    protected function getOrderHistory(?QuoteModel $quote = null): array
    {
        if (!$quote) {
            trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Calling the getOrderHistory method of the quote component model without passing the quote is deprecated.');
            $quote = $this->quote;
        }

        return [
            'numberOfCanceledOrders' => $this->getNumberOfCanceledOrders($quote),
            'numberOfCompletedOrders' => $this->getNumberOfCompletedOrders($quote),
            'numberOfUnpaidOrders' => $this->getNumberOfUnpaidOrders($quote),
        ];
    }

    /**
     * Create a md5 from the basket and customer to block recurring events.
     *
     * @return string
     */
    public function getQuoteHash(?QuoteModel $quote = null): string
    {
        if (!$quote) {
            trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Calling the getQuoteHash method of the quote component model without passing the quote is deprecated.');
        }
        $json = \json_encode($this->getNormalizedQuote($quote));
        return hash('sha256', $json);
    }

    /**
     * Get the number of canceled orders
     *
     * @return int
     */
    protected function getNumberOfCanceledOrders(?QuoteModel $quote = null)
    {
        if (!$quote) {
            trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Calling the getNumberOfCanceledOrders method of the quote component model without passing the quote is deprecated.');
            $quote = $this->quote;
        }

        return $this->getNumberOf([Order::STATE_CANCELED], $quote);
    }

    /**
     * Get the number of completed orders
     *
     * @return int
     */
    protected function getNumberOfCompletedOrders(?QuoteModel $quote = null)
    {
        if (!$quote) {
            trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Calling the getNumberOfCompletedOrders method of the quote component model without passing the quote is deprecated.');
            $quote = $this->quote;
        }

        return $this->getNumberOf([Order::STATE_COMPLETE], $quote);
    }

    /**
     * Get the number of unpaid orders
     *
     * @return int
     */
    protected function getNumberOfUnpaidOrders(?QuoteModel $quote = null): int
    {
        if (!$quote) {
            trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Calling the getNumberOfUnpaidOrders method of the quote component model without passing the quote is deprecated.');
            $quote = $this->quote;
        }

        return $this->getNumberOf([
            Order::STATE_PENDING_PAYMENT,
            Order::STATE_NEW,
            Order::STATE_HOLDED,
            Order::STATE_PROCESSING
        ], $quote);
    }

    /**
     * Get The Number of orders by given state
     *
     * @param array $states
     *
     * @return int
     */
    protected function getNumberOf(array $states, ?QuoteModel $quote = null): int
    {
        if (!$quote) {
            trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Calling the getNumberOf method of the quote component model without passing the quote is deprecated.');
            $quote = $this->quote;
        }

        $col = $this->orderFactory->create($quote->getCustomer()->getId());
        $col->addFieldToSelect('entity_id');
        $col->addFieldToFilter('state', $states);

        if (!$quote->getCustomer()->getId()) {
            $email = $this->getCustomerEmail($quote);
            $col->addFieldToFilter('customer_email', ['like' => $email]);
        }

        return $col->count();
    }

    /**
     * Try to extract the gender from a quote address. If this is not possible
     * a fallback is done to customer gender or prefix.
     *
     * @return string|null
     */
    protected function getGender(AddressModel $address): ?string
    {
        if ($address->getPrefix()) {
            $gender = $this->addressHelper->mapPrefixToGender($address->getPrefix());
            if ($gender) {
                return $gender;
            }
        }

        $quote = $address->getQuote();

        $gender = $quote->getCustomerGender() ?: $quote->getCustomer()->getGender();
        $gender = self::GENDER_MAPPING[$gender] ?? null;
        if ($gender) {
            return $gender;
        }

        $prefix = $quote->getCustomerPrefix() ?: $quote->getCustomer()->getPrefix();
        return $this->addressHelper->mapPrefixToGender((string) $prefix);
    }
}
