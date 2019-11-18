<?php

namespace Leonex\RiskManagementPlatform\Model\Component;

use Leonex\RiskManagementPlatform\Helper\Data;
use Leonex\RiskManagementPlatform\Model\Component;
use Leonex\RiskManagementPlatform\Model\Config\Source\CheckingTime;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\MethodInterface;

class Connector
{
    /** Dt.: Kreditentscheidung */
    const JUSTIFIABLE_INTEREST_LOAN_DECISION = 1;

    /** Dt.: Geschäftsanbahnung */
    const JUSTIFIABLE_INTEREST_BUSINESS_INITIATION = 3;

    /** Dt.: Forderung */
    const JUSTIFIABLE_INTEREST_CLAIM = 4;

    /** Dt.: Versicherungsvertrag */
    const JUSTIFIABLE_INTEREST_INSURANCE_CONTRACT = 5;

    /** Dt.: Beteiligungsverhältnisse */
    const JUSTIFIABLE_INTEREST_SHARING_STATUS = 6;

    /** Dt.: Überfällige Forderung */
    const JUSTIFIABLE_INTEREST_OVERDUE_CLAIM = 7;

    /** Dt.: Vollstreckungsauskunft */
    const JUSTIFIABLE_INTEREST_ENFORCEMENT_CLAIM = 8;

    /** Dt.: Konditionenanfrage (BDSG, §28a Abs. 2 Satz 4) (nur Finanzdienstleistungssektor) */
    const JUSTIFIABLE_INTEREST_TERMS_REQUEST = 9;

    /** @var Quote */
    protected $_quote;

    /** @var Data */
    protected $_helper;

    /** @var CacheInterface */
    protected $_cacheInterface;

    /** @var Api */
    protected $_api;

    /**
     * Connector constructor.
     *
     * @param Quote          $quote
     * @param Data           $helper
     * @param CacheInterface $cacheInterface
     * @param Api            $api
     */
    public function __construct(
        Component\Quote $quote, Data $helper, CacheInterface $cacheInterface, Api $api
    ) {
        $this->_quote = $quote;
        $this->_helper = $helper;
        $this->_cacheInterface = $cacheInterface;
        $this->_api = $api;
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
        if ($this->_justifyInterest($this->_quote)) {
            $content = $this->_quote->getNormalizedQuote();

            $this->_api->setConfiguration([
                'api_url' => $this->_helper->getApiUrl(), 'api_key' => $this->_helper->getApiKey()
            ]);

            /** @var Response $response */
            $response = $this->_api->post($content);
            $response->setHash($this->_quote);
            $this->_storeResponse($response);
        }
        $response = $this->_loadResponse($this->_quote->getQuoteHash());
        return $response->filterPayment($this->_getPaymentMethod($observer));
    }

    /**
     * Check if it's necessary to check payments.
     * Conditions:
     *
     * @param Observer $observer
     *
     * @return bool
     */
    public function verifyInterest(Observer $observer)
    {
        /** @var Data $helper */
        $helper = $this->_helper;
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
    protected function _justifyInterest(Quote $quote)
    {
        return !(bool)$this->_loadResponse($quote->getQuoteHash());
    }

    /**
     * Get Cache-Object
     *
     * @return CacheInterface
     */
    protected function _getCache()
    {
        return $this->_cacheInterface;
    }

    /**
     * Get the inner payment method from observer
     *
     * @param Observer $observer
     *
     * @return mixed
     */
    protected function _getPaymentMethod(Observer $observer)
    {
        $event = $observer->getEvent();
        return $event->getMethodInstance()->getCode();
    }

    /**
     * Store the response from the api-call.
     *
     * @param Response $response
     */
    protected function _storeResponse(Response $response)
    {
        $cache = $this->_getCache();
        $cache->save($response->getCleanResponse(), $response->getHash(), array(), 60 * 60 * 2);
    }

    /**
     * Get the response from the session and create a new Response object.
     *
     * @param $hash
     *
     * @return bool|Response
     */
    protected function _loadResponse($hash)
    {
        $cache = $this->_getCache();
        $response = $cache->load($hash);

        return $response ? new Response($response) : false;
    }
}
