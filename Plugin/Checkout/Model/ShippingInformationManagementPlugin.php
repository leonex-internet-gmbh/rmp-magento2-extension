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
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\QuoteRepository;

class ShippingInformationManagementPlugin
{
    protected $quoteRepository;
    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    public function __construct(
        QuoteRepository $quoteRepository,
        TimezoneInterface $localeDate
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->localeDate = $localeDate;
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

        // If the extension attribute 'edob' is not sent by the frontend and there are no other
        // extension attributes, $extAttributes will be null.
        if (!$extAttributes) {
            return;
        }

        $dob = $extAttributes->getEdob();
        try {
            $quote = $this->quoteRepository->getActive($cartId);
            if ($dob) {
                $quote->setCustomerDob($this->localeDate->date($dob)->format('d-m-Y'));
            }
        } catch (NoSuchEntityException $e) {

        }
    }

}
