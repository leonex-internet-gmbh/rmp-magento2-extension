<?php

namespace Leonex\RiskManagementPlatform\Model\Component;

use Leonex\RiskManagementPlatform\Helper\Data;
use Leonex\RiskManagementPlatform\Model\Component;
use Leonex\RiskManagementPlatform\Model\Logger;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\MethodInterface;

class Connector
{
    /**
     * Dt.: Kreditentscheidung
     */
    const JUSTIFIABLE_INTEREST_LOAN_DECISION = 1;

    /**
     * Dt.: Geschäftsanbahnung
     */
    const JUSTIFIABLE_INTEREST_BUSINESS_INITIATION = 3;

    /**
     * Dt.: Forderung
     */
    const JUSTIFIABLE_INTEREST_CLAIM = 4;

    /**
     * Dt.: Versicherungsvertrag
     */
    const JUSTIFIABLE_INTEREST_INSURANCE_CONTRACT = 5;

    /**
     * Dt.: Beteiligungsverhältnisse
     */
    const JUSTIFIABLE_INTEREST_SHARING_STATUS = 6;

    /**
     * Dt.: Überfällige Forderung
     */
    const JUSTIFIABLE_INTEREST_OVERDUE_CLAIM = 7;

    /**
     * Dt.: Vollstreckungsauskunft
     */
    const JUSTIFIABLE_INTEREST_ENFORCEMENT_CLAIM = 8;

    /**
     * Dt.: Konditionenanfrage (BDSG, §28a Abs. 2 Satz 4) (nur Finanzdienstleistungssektor)
     */
    const JUSTIFIABLE_INTEREST_TERMS_REQUEST = 9;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CacheInterface
     */
    protected $cacheInterface;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var Logger
     */
    protected $rmpLogger;

    /**
     * Connector constructor.
     *
     * @param Quote           $quote
     * @param Data            $helper
     * @param CacheInterface  $cacheInterface
     * @param Api             $api
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        Component\Quote $quote,
        Data $helper,
        CacheInterface $cacheInterface,
        Api $api,
        ResponseFactory $responseFactory,
        Logger $rmpLogger
    ) {
        $this->quote = $quote;
        $this->helper = $helper;
        $this->cacheInterface = $cacheInterface;
        $this->api = $api;
        $this->responseFactory = $responseFactory;
        $this->rmpLogger = $rmpLogger;
    }

    /**
     * Check if Paymentmethod is available
     *
     * @param string $paymentMethod
     *
     * @return bool
     */
    public function checkPaymentPre(string $paymentMethod): bool
    {
        if ($this->helper->isDebugLoggingEnabled()) {
            $this->rmpLogger->debug('Started payment check', ['payment_method' => $paymentMethod]);
        }

        $response = $this->loadCachedResponse($this->quote->getQuoteHash());

        if (!$response) {
            $content = $this->quote->getNormalizedQuote();

            try {
                /** @var Response $response */
                $response = $this->api->post($content);
                $response->setHash($this->quote);
                $this->storeResponse($response);
            } catch (\Exception $e) {
                // Error message will be logged in API adapter. Nothing to be done here.
                return false;
            }
        }

        $isAvailable = $response->filterPayment($paymentMethod);

        if ($this->helper->isDebugLoggingEnabled()) {
            $msg = $isAvailable ? 'Payment method is available' : 'Payment method is not available';
            $this->rmpLogger->debug($msg, ['payment_method' => $paymentMethod]);
        }

        return $isAvailable;
    }

    /**
     * Check if it's necessary to check payments.
     * Conditions:
     *
     * @param Observer $observer
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isCheckNeeded(Observer $observer)
    {
        /** @var Data $helper */
        $helper = $this->helper;
        if ($helper->isAdmin() || !$helper->isActive() || !$this->quote->isAddressProvided()) {
            return false;
        }

        $event = $observer->getEvent();
        $method = $event->getMethodInstance();
        if ($method instanceof MethodInterface) {
            $result = $event->getResult();
            if ($result->getData('is_available')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Cache-Object
     *
     * @return CacheInterface
     */
    protected function getCache()
    {
        return $this->cacheInterface;
    }

    /**
     * Store the response from the api-call.
     *
     * @param Response $response
     */
    protected function storeResponse(Response $response)
    {
        $cache = $this->getCache();
        $cache->save($response->getCleanResponse(), $response->getHash(), array(), 60 * 60 * 2);
    }

    /**
     * Get the response from the session and create a new Response object.
     *
     * @param $hash
     *
     * @return null|Response
     */
    protected function loadCachedResponse($hash)
    {
        $cache = $this->getCache();
        $response = $cache->load($hash);

        if ($response && $this->helper->isDebugLoggingEnabled()) {
            $this->rmpLogger->debug('Loaded API response from cache.', ['response' => $response]);
        }

        return $response ? $this->responseFactory->create(['jsonString' => $response]) : null;
    }
}
