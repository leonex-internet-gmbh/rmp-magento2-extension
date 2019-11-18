<?php
/**
 * ShippingInformationManagementPlugin.php
 *
 * @category    Leonex
 * @package     ProDruck
 * @author      Thomas Hampe <hampe@leonex.de>
 * @copyright   Copyright (c) 2019, LEONEX Internet GmbH
 */


namespace Leonex\RiskManagementPlatform\Plugin\Checkout\Model;


use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;

class ShippingInformationManagementPlugin
{
    protected $quoteRepository;

    public function __construct(
        QuoteRepository $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param ShippingInformationManagement $subject
     * @param $cartId
     * @param ShippingInformationInterface $addressInformation
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $extAttributes = $addressInformation->getExtensionAttributes();
        $dob = $extAttributes->getEdob();
        try {
            $quote = $this->quoteRepository->getActive($cartId);
            if ($dob) {
                $quote->setCustomerDob($dob);
            }
        } catch (NoSuchEntityException $e) {

        }
    }

}
