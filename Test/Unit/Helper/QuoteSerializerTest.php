<?php

namespace Leonex\RiskManagementPlatform\Test\Unit\Model\Component;

use Leonex\RiskManagementPlatform\Helper\Address as AddressHelper;
use Leonex\RiskManagementPlatform\Helper\CheckoutStatus as CheckoutStatusHelper;
use Leonex\RiskManagementPlatform\Helper\QuoteSerializer;
use Leonex\RiskManagementPlatform\Model\Component\Connector;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use PHPUnit\Framework\TestCase;

class QuoteSerializerTest extends TestCase
{
    /** @var ObjectManager */
    private $objectManager;

    /** @var \Magento\Framework\ObjectManager\FactoryInterface */
    private $objectFactory;

    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var AddressHelper */
    private $addressHelper;

    /** @var CheckoutStatusHelper */
    private $checkoutStatusHelper;

    /** @var CollectionFactoryInterface */
    private $collectionFactory;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    private $connection;

    /** @var \Magento\Quote\Model\ResourceModel\Quote\Address */
    private $addressResource;

    /** @var \Magento\Quote\Model\ResourceModel\Quote\Item */
    private $itemResource;

    private $storeManager;

    /** @var \Magento\Framework\Event\ManagerInterface */
    private $eventDispatcher;

    /** @var \Magento\Framework\Model\Context */
    private $context;

    /** @var QuoteSerializer  */
    private $quoteSerializer;

    protected function setUp(): void
    {
        $this->objectFactory = $this->createMock(\Magento\Framework\ObjectManager\FactoryInterface::class);
        $this->objectFactory->method('create')->willReturnCallback(function ($type, $arguments) {
            switch ($type) {
                case \Magento\Quote\Model\ResourceModel\Quote\Address\Collection::class:
                    return $this->instantiateEmptyAddressCollection();
            }
        });
        $objectConfig = $this->createMock(\Magento\Framework\ObjectManager\ConfigInterface::class);
        $objectConfig->method('getPreference')->willReturnArgument(0);
        $this->objectManager = new ObjectManager($this->objectFactory, $objectConfig);

        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->addressHelper = $this->createMock(AddressHelper::class);
        $this->addressHelper->method('mapPrefixToGender')->willReturnCallback(function ($prefix) {
            return QuoteSerializer::GENDER_MAPPING[$prefix] ?? null;
        });
        $this->checkoutStatusHelper = $this->createMock(CheckoutStatusHelper::class);
        $orderCollection = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $orderCollection->method('count')->willReturn(0);
        $this->collectionFactory = $this->createMock(CollectionFactoryInterface::class);
        $this->collectionFactory->method('create')->willReturn($orderCollection);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);

        $store = $this->createMock(\Magento\Store\Api\Data\StoreInterface::class);

        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->eventDispatcher = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $this->context = $this->createMock(\Magento\Framework\Model\Context::class);
        $this->context->method('getEventDispatcher')->willReturn($this->eventDispatcher);

        $select = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->connection->method('select')->willReturn($select);

        $this->addressResource = $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Address::class);
        $this->addressResource->method('getConnection')->willReturn($this->connection);

        $this->itemResource = $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Item::class);
        $this->itemResource->method('getConnection')->willReturn($this->connection);

        $this->quoteSerializer = new QuoteSerializer($this->checkoutSession, $this->addressHelper, $this->checkoutStatusHelper, $this->collectionFactory);
    }

    /**
     * @covers QuoteSerializer::getNormalizedQuote
     */
    public function testGetNormalizedQuote_WithAllData(): void
    {
        // PREPARE
        $quote = $this->instantiateEmptyQuoteModel([
            'entity_id' => 115800,
            'customer_id' => 1204,
            'grand_total' => 13.99,
        ]);
        $billingAddress = $quote->getBillingAddress();
        $billingAddress->setQuote($quote);
        $billingAddress->addData([
            'prefix' => 1,
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'street' => ['Querweg 5'],
            'postcode' => '12345',
            'city' => 'Bonn',
            'country_id' => 'DE',
            'email' => 'max@example.com',
        ]);
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setQuote($quote);
        $shippingAddress->addData([
            'prefix' => 1,
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'company' => 'LEONEX Internet GmbH',
            'street' => ['Technologiepark 6'],
            'postcode' => '33100',
            'city' => 'Paderborn',
            'country_id' => 'DE',
        ]);
        $quote->addItem($this->instantiateEmptyQuoteItemModel([
            'sku' => '345-9876',
            'qty' => 1,
            'price_incl_tax' => 5.00,
            'row_total' => 5.00,
        ]));
        $quote->addItem($this->instantiateEmptyQuoteItemModel([
            'sku' => '122-5645',
            'qty' => 1,
            'price_incl_tax' => 8.99,
            'row_total' => 8.99,
        ]));
        $this->checkoutStatusHelper->method('hasBillingAddressReallyBeenSet')->willReturn(true);

        // ACTION
        $data = $this->quoteSerializer->getNormalizedQuote($quote);

        // ASSERTIONS
        $this->assertEquals([
            'quoteId' => 115800,
            'justifiableInterest' => 3,
            'consentClause' => true,
            'billingAddress' => [
                'gender' => 'm',
                'lastName' => 'Mustermann',
                'firstName' => 'Max',
                'dateOfBirth' => null,
                'birthName' => '',
                'street' => 'Querweg 5',
                'zip' => '12345',
                'city' => 'Bonn',
                'country' => 'de',
            ],
            'shippingAddress' => [
                'gender' => 'm',
                'lastName' => 'Mustermann',
                'firstName' => 'Max',
                'dateOfBirth' => null,
                'birthName' => '',
                'street' => 'Technologiepark 6',
                'zip' => '33100',
                'city' => 'Paderborn',
                'country' => 'de',
            ],
            'quote' => [
                'items' => [
                    [
                        'sku' => '345-9876',
                        'quantity' => 1,
                        'price' => 5.00,
                        'rowTotal' => 5.00,
                    ],
                    [
                        'sku' => '122-5645',
                        'quantity' => 1,
                        'price' => 8.99,
                        'rowTotal' => 8.99,
                    ],
                ],
                'totalAmount' => 13.99,
            ],
            'customer' => [
                'number' => 1204,
                'email' => 'max@example.com',
            ],
            'orderHistory' => [
                'numberOfCanceledOrders' => 0,
                'numberOfCompletedOrders' => 0,
                'numberOfUnpaidOrders' => 0,
            ],
        ], $data);
    }

    /**
     * @covers QuoteSerializer::getNormalizedQuote
     */
    public function testGetNormalizedQuote_WithShippingAddressNormalizedToNull_IfOnlyCountryIsSet(): void
    {
        // PREPARE
        $quote = $this->instantiateEmptyQuoteModel([
            'entity_id' => 115800,
            'customer_id' => 1204,
            'grand_total' => 0.00,
        ]);

        $billingAddress = $quote->getBillingAddress();
        $billingAddress->setQuote($quote);
        $billingAddress->addData([
            'prefix' => 1,
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'street' => ['Querweg 5'],
            'postcode' => '12345',
            'city' => 'Bonn',
            'country_id' => 'DE',
            'email' => 'max@example.com',
        ]);
        $this->checkoutStatusHelper->method('hasBillingAddressReallyBeenSet')->willReturn(true);

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setQuote($quote);
        $shippingAddress->addData([
            'country_id' => 'DE',
        ]);

        // ACTION
        $data = $this->quoteSerializer->getNormalizedQuote($quote);

        // ASSERTIONS
        $this->assertNull($data['shippingAddress']);
    }

    private function instantiateEmptyQuoteModel(array $data = []): Quote
    {
        $quoteAddressFactory = $this->createMock(\Magento\Quote\Model\Quote\AddressFactory::class);
        $quoteAddressFactory->method('create')->willReturnCallback(function () {
            return $this->instantiateEmptyAddressModel();
        });

        $quoteItemCollection = $this->instantiateEmptyItemCollection();
        $data['items_collection'] = $quoteItemCollection;

        // Prevent the collection from trying to load data from database
        $prop = new \ReflectionProperty(get_class($quoteItemCollection), '_isCollectionLoaded');
        $prop->setAccessible(true);
        $prop->setValue($quoteItemCollection, true);

        $customer = $this->createMock(\Magento\Customer\Model\Customer::class);
        $customer->method('getId')->willReturn(null);
        $customerRepository = $this->createMock(\Magento\Customer\Api\CustomerRepositoryInterface::class);
        $customerRepository->method('getById')->willReturn($customer);

        $resource = $this->createMock(\Magento\Quote\Model\ResourceModel\Quote::class);
        $resource->method('getIdFieldName')->willReturn('entity_id');

        return new Quote(
            /*context:*/ $this->context,
            /*registry:*/ $this->createMock(\Magento\Framework\Registry::class),
            /*extensionFactory:*/ $this->createMock(\Magento\Framework\Api\ExtensionAttributesFactory::class),
            /*customAttributeFactory:*/ $this->createMock(\Magento\Framework\Api\AttributeValueFactory::class),
            /*quoteValidator:*/ $this->createMock(\Magento\Quote\Model\QuoteValidator::class),
            /*catalogProduct:*/ $this->createMock(\Magento\Catalog\Helper\Product::class),
            /*scopeConfig:*/ $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class),
            /*storeManager:*/ $this->storeManager,
            /*config:*/ $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class),
            /*quoteAddressFactory:*/ $quoteAddressFactory,
            /*customerFactory:*/ $this->createMock(\Magento\Customer\Model\CustomerFactory::class),
            /*groupRepository:*/ $this->createMock(\Magento\Customer\Api\GroupRepositoryInterface::class),
            /*quoteItemCollectionFactory:*/ $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory::class),
            /*quoteItemFactory:*/ $this->createMock(\Magento\Quote\Model\Quote\ItemFactory::class),
            /*messageFactory:*/ $this->createMock(\Magento\Framework\Message\Factory::class),
            /*statusListFactory:*/ $this->createMock(\Magento\Sales\Model\Status\ListFactory::class),
            /*productRepository:*/ $this->createMock(\Magento\Catalog\Api\ProductRepositoryInterface::class),
            /*quotePaymentFactory:*/ $this->createMock(\Magento\Quote\Model\Quote\PaymentFactory::class),
            /*quotePaymentCollectionFactory:*/ $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Payment\CollectionFactory::class),
            /*objectCopyService:*/ $this->createMock(\Magento\Framework\DataObject\Copy::class),
            /*stockRegistry:*/ $this->createMock(\Magento\CatalogInventory\Api\StockRegistryInterface::class),
            /*itemProcessor:*/ $this->createMock(\Magento\Quote\Model\Quote\Item\Processor::class),
            /*objectFactory:*/ $this->createMock(\Magento\Framework\DataObject\Factory::class),
            /*addressRepository:*/ $this->createMock(\Magento\Customer\Api\AddressRepositoryInterface::class),
            /*criteriaBuilder:*/ $this->createMock(\Magento\Framework\Api\SearchCriteriaBuilder::class),
            /*filterBuilder:*/ $this->createMock(\Magento\Framework\Api\FilterBuilder::class),
            /*addressDataFactory:*/ $this->createMock(\Magento\Customer\Api\Data\AddressInterfaceFactory::class),
            /*customerDataFactory:*/ $this->createMock(\Magento\Customer\Api\Data\CustomerInterfaceFactory::class),
            /*customerRepository:*/ $customerRepository,
            /*dataObjectHelper:*/ $this->createMock(\Magento\Framework\Api\DataObjectHelper::class),
            /*extensibleDataObjectConverter:*/ $this->createMock(\Magento\Framework\Api\ExtensibleDataObjectConverter::class),
            /*currencyFactory:*/ $this->createMock(\Magento\Quote\Model\Cart\CurrencyFactory::class),
            /*extensionAttributesJoinProcessor:*/ $this->createMock(\Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface::class),
            /*totalsCollector:*/ $this->createMock(\Magento\Quote\Model\Quote\TotalsCollector::class),
            /*totalsReader:*/ $this->createMock(\Magento\Quote\Model\Quote\TotalsReader::class),
            /*shippingFactory:*/ $this->createMock(\Magento\Quote\Model\ShippingFactory::class),
            /*shippingAssignmentFactory:*/ $this->createMock(\Magento\Quote\Model\ShippingAssignmentFactory::class),
            /*resource:*/ $resource,
            /*resourceCollection:*/ $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Collection::class),
            /*data:*/ $data,
            /*orderIncrementIdChecker:*/ $this->createMock(\Magento\Sales\Model\OrderIncrementIdChecker::class),
            /*allowedCountriesReader:*/ $this->createMock(\Magento\Directory\Model\AllowedCountries::class)
        );
    }

    private function instantiateEmptyAddressModel(array $data = []): Address
    {
        return new Address(
            /*context:*/ $this->context,
            /*registry:*/ $this->createMock(\Magento\Framework\Registry::class),
            /*extensionFactory:*/ $this->createMock(\Magento\Framework\Api\ExtensionAttributesFactory::class),
            /*customAttributeFactory:*/ $this->createMock(\Magento\Framework\Api\AttributeValueFactory::class),
            /*directoryData:*/ $this->createMock(\Magento\Directory\Helper\Data::class),
            /*eavConfig:*/ $this->createMock(\Magento\Eav\Model\Config::class),
            /*addressConfig:*/ $this->createMock(\Magento\Customer\Model\Address\Config::class),
            /*regionFactory:*/ $this->createMock(\Magento\Directory\Model\RegionFactory::class),
            /*countryFactory:*/ $this->createMock(\Magento\Directory\Model\CountryFactory::class),
            /*metadataService:*/ $this->createMock(\Magento\Customer\Api\AddressMetadataInterface::class),
            /*addressDataFactory:*/ $this->createMock(\Magento\Customer\Api\Data\AddressInterfaceFactory::class),
            /*regionDataFactory:*/ $this->createMock(\Magento\Customer\Api\Data\RegionInterfaceFactory::class),
            /*dataObjectHelper:*/ $this->createMock(\Magento\Framework\Api\DataObjectHelper::class),
            /*scopeConfig:*/ $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class),
            /*addressItemFactory:*/ $this->createMock(\Magento\Quote\Model\Quote\Address\ItemFactory::class),
            /*itemCollectionFactory:*/ $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Address\Item\CollectionFactory::class),
            /*addressRateFactory:*/ $this->createMock(\Magento\Quote\Model\Quote\Address\RateFactory::class),
            /*rateCollector:*/ $this->createMock(\Magento\Quote\Model\Quote\Address\RateCollectorInterfaceFactory::class),
            /*rateCollectionFactory:*/ $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Address\Rate\CollectionFactory::class),
            /*rateRequestFactory:*/ $this->createMock(\Magento\Quote\Model\Quote\Address\RateRequestFactory::class),
            /*totalCollectorFactory:*/ $this->createMock(\Magento\Quote\Model\Quote\Address\Total\CollectorFactory::class),
            /*addressTotalFactory:*/ $this->createMock(\Magento\Quote\Model\Quote\Address\TotalFactory::class),
            /*objectCopyService:*/ $this->createMock(\Magento\Framework\DataObject\Copy::class),
            /*carrierFactory:*/ $this->createMock(\Magento\Shipping\Model\CarrierFactoryInterface::class),
            /*validator:*/ $this->createMock(\Magento\Quote\Model\Quote\Address\Validator::class),
            /*addressMapper:*/ $this->createMock(\Magento\Customer\Model\Address\Mapper::class),
            /*attributeList:*/ $this->createMock(\Magento\Quote\Model\Quote\Address\CustomAttributeListInterface::class),
            /*totalsCollector:*/ $this->createMock(\Magento\Quote\Model\Quote\TotalsCollector::class),
            /*totalsReader:*/ $this->createMock(\Magento\Quote\Model\Quote\TotalsReader::class),
            /*resource:*/ $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Address::class),
            /*resourceCollection:*/ $this->instantiateEmptyAddressCollection(),
            /*data:*/ $data,
            /*serializer:*/ $this->createMock(\Magento\Framework\Serialize\Serializer\Json::class),
            /*storeManager:*/ $this->storeManager,
            /*compositeValidator:*/ $this->createMock(\Magento\Customer\Model\Address\CompositeValidator::class),
            /*countryModelsCache:*/ $this->createMock(\Magento\Customer\Model\Address\AbstractAddress\CountryModelsCache::class),
            /*regionModelsCache:*/ $this->createMock(\Magento\Customer\Model\Address\AbstractAddress\RegionModelsCache::class)
        );
    }

    private function instantiateEmptyQuoteItemModel(array $data = []): Item
    {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('isDeleted')->willReturn(false);
        $product->method('getStatus')->willReturn(ProductStatus::STATUS_ENABLED);
        $data['product'] = $product;

        return new Item(
            /*context:*/ $this->context,
            /*registry:*/ $this->createMock(\Magento\Framework\Registry::class),
            /*extensionFactory:*/ $this->createMock(\Magento\Framework\Api\ExtensionAttributesFactory::class),
            /*customAttributeFactory:*/ $this->createMock(\Magento\Framework\Api\AttributeValueFactory::class),
            /*productRepository:*/ $this->createMock(\Magento\Catalog\Api\ProductRepositoryInterface::class),
            /*priceCurrency:*/ $this->createMock(\Magento\Framework\Pricing\PriceCurrencyInterface::class),
            /*statusListFactory:*/ $this->createMock(\Magento\Sales\Model\Status\ListFactory::class),
            /*localeFormat:*/ $this->createMock(\Magento\Framework\Locale\FormatInterface::class),
            /*itemOptionFactory:*/ $this->createMock(\Magento\Quote\Model\Quote\Item\OptionFactory::class),
            /*quoteItemCompare:*/ $this->createMock(\Magento\Quote\Model\Quote\Item\Compare::class),
            /*stockRegistry:*/ $this->createMock(\Magento\CatalogInventory\Api\StockRegistryInterface::class),
            /*resource:*/ $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Item::class),
            /*resourceCollection:*/ $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Item\Collection::class),
            $data,
            /*serializer:*/ $this->createMock(\Magento\Framework\Serialize\Serializer\Json::class),
            /*itemOptionComparator:*/ $this->createMock(\Magento\Quote\Model\Quote\Item\Option\ComparatorInterface::class)
        );
    }

    private function instantiateEmptyAddressCollection(): \Magento\Quote\Model\ResourceModel\Quote\Address\Collection
    {
        return new \Magento\Quote\Model\ResourceModel\Quote\Address\Collection(
            /*entityFactory:*/ $this->createMock(\Magento\Framework\Data\Collection\EntityFactoryInterface::class),
            /*logger:*/ $this->createMock(\Psr\Log\LoggerInterface::class),
            /*fetchStrategy:*/ $this->createMock(\Magento\Framework\Data\Collection\Db\FetchStrategyInterface::class),
            /*eventManager:*/ $this->createMock(\Magento\Framework\Event\ManagerInterface::class),
            /*entitySnapshot:*/ $this->createMock(\Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot::class),
            /*connection:*/ $this->connection,
            /*resource:*/ $this->addressResource
        );
    }

    private function instantiateEmptyItemCollection(): \Magento\Quote\Model\ResourceModel\Quote\Item\Collection
    {
        return new \Magento\Quote\Model\ResourceModel\Quote\Item\Collection(
            /*entityFactory:*/ $this->createMock(\Magento\Framework\Data\Collection\EntityFactory::class),
            /*logger:*/ $this->createMock(\Psr\Log\LoggerInterface::class),
            /*fetchStrategy:*/ $this->createMock(\Magento\Framework\Data\Collection\Db\FetchStrategyInterface::class),
            /*eventManager:*/ $this->createMock(\Magento\Framework\Event\ManagerInterface::class),
            /*entitySnapshot:*/ $this->createMock(\Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot::class),
            /*itemOptionCollectionFactory:*/ $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Item\Option\CollectionFactory::class),
            /*productCollectionFactory:*/ $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class),
            /*quoteConfig:*/ $this->createMock(\Magento\Quote\Model\Quote\Config::class),
            /*connection:*/ $this->connection,
            /*resource:*/ $this->itemResource,
            /*storeManager:*/ $this->storeManager,
            /*config:*/ $this->createMock(\Magento\Quote\Model\Config::class)
        );
    }
}
