<?php

namespace Leonex\RiskManagementPlatform\Model\Component;

use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;

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
class Response extends DataObject
{

    /**
     * @var  mixed $hash
     */
    protected $hash;

    protected $response;


    public function __construct(Json $serializer, $jsonString)
    {
        $this->response = $jsonString;
        $json = $serializer->unserialize($jsonString);
        parent::__construct($json);
    }

    /**
     * Get the payments given by the main event as argument and filter them with new conditions from the response.
     *
     * When a payment is marked as unavailable (available != true) then remove this payment from the array.
     * A array with
     *
     * @param string $payment
     *
     * @return bool
     */
    public function filterPayment($payment): bool
    {
        // payment method is not in response
        if (!isset($this->_data['payment_methods'][$payment])) {
            return true;
        }

        return (bool) ($this->_data['payment_methods'][$payment]['available'] ?? false);
    }

    /**
     * Set Hash from given Quote
     *
     * @param Quote $quote
     */
    public function setHash(Quote $quote)
    {
        $this->hash = $quote->getQuoteHash();
    }

    /**
     * Return Hash from Quote
     *
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Return Response as json
     *
     * @return mixed
     */
    public function getCleanResponse()
    {
        return $this->response;
    }
}
