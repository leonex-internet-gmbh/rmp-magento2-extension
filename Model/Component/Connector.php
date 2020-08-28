<?php

namespace Leonex\RiskManagementPlatform\Model\Component;

use Leonex\RiskManagementPlatform\Helper\Data;
use Leonex\RiskManagementPlatform\Helper\Logging;
use Leonex\RiskManagementPlatform\Model\Component;
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
     * @var Logging
     */
    protected $loggingHelper;

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
        Logging $loggingHelper,
        CacheInterface $cacheInterface,
        Api $api,
        ResponseFactory $responseFactory
    ) {
        $this->quote = $quote;
        $this->helper = $helper;
        $this->loggingHelper = $loggingHelper;
        $this->cacheInterface = $cacheInterface;
        $this->api = $api;
        $this->responseFactory = $responseFactory;
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
        $this->loggingHelper->log('debug', 'Started payment check.', 'check', ['payment_method' => $paymentMethod], $this->quote->getQuoteId());

        $response = $this->loadCachedResponse($this->quote->getQuoteHash());

        if (!$response) {
            $content = $this->quote->getNormalizedQuote();

            try {
                /** @var Response $response */
                $response = $this->api->post($content);
                $response->setHash($this->quote);
                $this->storeResponse($response);
            } catch (\Exception $e) {
                // Error message will be logged in API adapter.
                // We fall back to the modules setting for maximum grand total when platform is offline.
                $maxGrandTotal = $this->helper->getMaxGrandTotalWhenOffline();
                $isAvailable = bccomp($maxGrandTotal, $this->quote->getGrandTotal(), 2) >= 0;

                if ($isAvailable) {
                    $msg = sprintf('Payment method "%s" is available although RMP is offline (as it was configured).', $paymentMethod);
                } else {
                    $msg = sprintf('Payment method "%s" is not available because RMP is offline.', $paymentMethod);
                }
                $this->loggingHelper->logToFile('info', $msg, 'check', [
                    'payment_method' => $paymentMethod,
                    'max_grand_total_when_offline' => $maxGrandTotal,
                    'order_total' => $this->quote->getGrandTotal(),
                ], $this->quote->getQuoteId());

                return $isAvailable;
            }
        }

        $isAvailable = $response->filterPayment($paymentMethod);

        $msg = $isAvailable ? 'Payment method "%s" is available.' : 'Payment method "%s" is not available.';
        $msg = sprintf($msg, $paymentMethod);
        $this->loggingHelper->logToFile('info', $msg, 'check', [
            'payment_method' => $paymentMethod,
            'response' => $response->getCleanResponse(),
        ], $this->quote->getQuoteId());

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
        if ($helper->isAdmin() || !$helper->isActive()) {
            return false;
        }

        if (!$this->quote->isAddressProvided()) {
            $this->loggingHelper->log('debug', 'No address data provided.', 'check', [], $this->quote->getQuoteId());
            return false;
        }

        $event = $observer->getEvent();
        $method = $event->getMethodInstance();
        if ($method instanceof MethodInterface) {
            $paymentMethodsToCheck = $helper->getPaymentMethodsToCheck();
            if (!in_array($method->getCode(), $paymentMethodsToCheck, true)) {
                $msg = sprintf('Payment method "%s" not selected to check.', $method->getCode());
                $this->loggingHelper->log('debug', $msg, 'check', ['payment_method' => $method->getCode()], $this->quote->getQuoteId());
                return false;
            }

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

        if ($response) {
            $this->loggingHelper->log('info', 'Loaded API response from cache.', 'check', ['response' => $response], $this->quote->getQuoteId());
        }

        return $response ? $this->responseFactory->create(['jsonString' => $response]) : null;
    }
}
