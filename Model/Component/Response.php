<?php

namespace Leonex\RiskManagementPlatform\Model\Component;

/**
 * Class Response
 *
 * Manage the response from the api-call.
 * Main goal of this class is a storage of the response in a structured way.
 * Beneath the structuring of the data the class implements a function to filter the payments from the main event.
 *
 * @package LxRmp\Components\Data
 * @author  fseeger
 */
class Response
{
    /** @var string */
    protected $status;

    /** @var \stdClass */
    protected $_payments;

    /** @var  mixed $_hash */
    protected $_hash;

    /** @var mixed $_response */
    protected $_response;

    /**
     * @param $response
     */
    public function __construct($response)
    {
        $this->_response = $response;
        $response = json_decode($this->_response);
        $this->status = $response->status;
        $this->_payments = $response->payment_methods;
    }

    /**
     * Get the payments given by the main event as argument and filter them with new conditions from the response.
     *
     * When a payment is marked as unavailable (available != true) then remove this payment from the array.
     * A array with
     *
     * @param $payment
     *
     * @return bool
     */
    public function filterPayment($payment)
    {
        if (property_exists($this->_payments, $payment)) {
            $obj = $this->_payments->$payment;
            if (!$obj->available) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Set Hash from given Quote
     *
     * @param Quote $quote
     */
    public function setHash(Quote $quote)
    {
        $this->_hash = $quote->getQuoteHash();
    }

    /**
     * Return Hash from Quote
     *
     * @return mixed
     */
    public function getHash()
    {
        return $this->_hash;
    }

    /**
     * Return Response as json
     *
     * @return mixed
     */
    public function getCleanResponse()
    {
        return $this->_response;
    }
}
