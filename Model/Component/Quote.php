<?php

namespace Leonex\RiskManagementPlatform\Model\Component;

use Leonex\RiskManagementPlatform\Helper\Address;
use Leonex\RiskManagementPlatform\Helper\CheckoutStatus;
use Leonex\RiskManagementPlatform\Helper\QuoteSerializer;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\Address as AddressModel;
use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;

/**
 * @deprecad since 2.3.0 - use \Leonex\RiskManagementPlatform\Helper\QuoteSerializer instead.
 */
class Quote extends QuoteSerializer
{
    /**
     * @var QuoteModel
     */
    protected $quote;

    /**
     * Quote constructor.
     *
     * @deprecad since 2.3.0 - use \Leonex\RiskManagementPlatform\Helper\QuoteSerializer instead.
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
        trigger_deprecation('leonex/magento-module-rmp-connector', '2.3.0', 'Usage of \Leonex\RiskManagementPlatform\Component\Quote is deprecated. Use \Leonex\RiskManagementPlatform\Helper\QuoteSerializer instead.');

        parent::__construct($checkoutSession, $addressHelper, $checkoutStatus, $orderFactory);
        $this->quote = $this->checkoutSession->getQuote();
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

}
