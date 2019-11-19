<?php

namespace Leonex\RiskManagementPlatform\Model\Component;

use Leonex\RiskManagementPlatform\Helper\Data;
use Leonex\RiskManagementPlatform\Model\Component;
use Leonex\RiskManagementPlatform\Model\Config\Source\CheckingTime;
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
        ResponseFactory $responseFactory
    ) {
        $this->quote = $quote;
        $this->helper = $helper;
        $this->cacheInterface = $cacheInterface;
        $this->api = $api;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Check if Paymentmethod is available
     *
     * @param Observer $observer
     *
     * @return bool
     */
    public function checkPaymentPre(Observer $observer)
    {
        if ($this->justifyInterest($this->quote)) {
            $content = $this->quote->getNormalizedQuote();

            $this->api->setConfiguration([
                'api_url' => $this->helper->getApiUrl(), 'api_key' => $this->helper->getApiKey()
            ]);

            /** @var Response $response */
            $response = $this->api->post($content);
            $response->setHash($this->quote);
            $this->storeResponse($response);
        }
        $response = $this->loadResponse($this->quote->getQuoteHash());
        return $response->filterPayment($this->getPaymentMethod($observer));
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
    public function verifyInterest(Observer $observer)
    {
        /** @var Data $helper */
        $helper = $this->helper;
        $event = $observer->getEvent();

        if (!$helper->isAdmin() && $helper->isActive()) {
            if ($helper->getTimeOfChecking() == CheckingTime::CHECKING_TIME_PRE) {
                if ($event->getMethodInstance() instanceof MethodInterface) {
                    $result = $event->getResult();
                    if ($result->getData('is_available')) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Check if the basket and customer data has any changes.
     * If not then load the old response from the session.
     *
     * @param Quote $quote
     *
     * @return bool
     */
    protected function justifyInterest(Quote $quote)
    {
        return !(bool)$this->loadResponse($quote->getQuoteHash());
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
     * Get the inner payment method from observer
     *
     * @param Observer $observer
     *
     * @return mixed
     */
    protected function getPaymentMethod(Observer $observer)
    {
        $event = $observer->getEvent();
        return $event->getMethodInstance()->getCode();
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
     * @return bool|Response
     */
    protected function loadResponse($hash)
    {
        $cache = $this->getCache();
        $response = $cache->load($hash);

        return $response ? $this->responseFactory->create(['jsonString' => $response]) : false;
    }
}
