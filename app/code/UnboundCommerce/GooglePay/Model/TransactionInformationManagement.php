<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model;

use Magento\Checkout\Api\TotalsInformationManagementInterface;
use Magento\Quote\Api\ShipmentEstimationInterface;
use UnboundCommerce\GooglePay\Api\TransactionInformationManagementInterface;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Model\Checkout\Helper\AddressHelper;

/**
 * Class TransactionInformationManagement
 */
class TransactionInformationManagement implements TransactionInformationManagementInterface
{

    /**
     * AddressHelper.
     *
     * @var AddressHelper
     */
    protected $addressHelper;

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * Quote repository.
     *
     * @var ShipmentEstimationInterface
     */
    protected $shipmentEstimation;

    /**
     * Cart total repository.
     *
     * @var TotalsInformationManagementInterface
     */
    protected $totalsInformationManagement;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param AddressHelper                              $addressHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param TotalsInformationManagementInterface       $totalsInformationManagement
     * @param ShipmentEstimationInterface                $shipmentEstimation
     * @param Logger                                     $logger
     */
    public function __construct(
        AddressHelper $addressHelper,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        TotalsInformationManagementInterface $totalsInformationManagement,
        ShipmentEstimationInterface $shipmentEstimation,
        Logger $logger
    ) {
        $this->addressHelper = $addressHelper;
        $this->cartRepository = $cartRepository;
        $this->totalsInformationManagement = $totalsInformationManagement;
        $this->shipmentEstimation = $shipmentEstimation;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function calculate($cartId, $intermediatePaymentData)
    {
        /**
 * @var \Magento\Quote\Model\Quote $quote 
*/
        $quote = $this->cartRepository->get($cartId);
        $store = $quote->getStore();
        $callbackTrigger = $intermediatePaymentData->getCallbackTrigger();
        $totalsInformation = $intermediatePaymentData->getTotalsInformation();
        $data = [];

        try {
            $regionCode = $totalsInformation->getAddress()->getRegionCode();
            $countryCode = $totalsInformation->getAddress()->getCountryId();
            if ($regionCode && !in_array($regionCode, array('US', 'CA'))) {
                $newRegionCode = $this->addressHelper->getRegionCodeByName($regionCode, $countryCode);
                if ($newRegionCode) {
                    $totalsInformation->getAddress()->setRegionCode($newRegionCode);
                }
            }

            if ($callbackTrigger == "INITIALIZE" || $callbackTrigger == "SHIPPING_ADDRESS") {
                $shippingInfo = $this->getShippingOptions($cartId, $store, $totalsInformation);
                if ($shippingInfo['transactionState'] === 'SUCCESS') {
                    $data['newShippingOptionParameters']['shippingOptions'] = $shippingInfo['shippingOptions'];
                    $data['newShippingOptionParameters']['defaultSelectedOptionId'] = $shippingInfo['defaultSelectedOption'];
                } else {
                    $result['paymentResultUpdate'] = $shippingInfo;
                    return $result;
                }
            }

            $transactionInfo = $this->getTransactionInfo($cartId, $totalsInformation);
            $data['newTransactionInfo'] = $transactionInfo;
        } catch (\Exception $e) {
            $this->logger->addError($e->getMessage());
            $data['transactionState'] = 'ERROR';
            $data['error'] = [];
            if ($callbackTrigger == "INITIALIZE" || $callbackTrigger == "SHIPPING_ADDRESS") {
                $data['error']['reason'] = 'SHIPPING_ADDRESS_INVALID';
                $data['error']['message'] = 'Error validating shipping information, please enter the correct address and try again';
                $data['error']['intent'] = 'SHIPPING_ADDRESS';
            } else {
                $data['error']['reason'] = 'SHIPPING_OPTION_INVALID';
                $data['error']['message'] = 'Unable to update shipping options';
                $data['error']['intent'] = 'SHIPPING_OPTION';
            }
        }

        $result['paymentResultUpdate'] = $data;
        return $result;
    }

    /**
     * Estimate shipping methods
     *
     * @param  string                                                $cartId
     * @param  string                                                $store
     * @param  \Magento\Checkout\Api\Data\TotalsInformationInterface $totalsInformation
     * @return array
     */
    protected function getShippingOptions($cartId, $store, $totalsInformation)
    {
        $data = [];
        $shippingOptions = [];
        $shippingMethodAssigned = false;
        $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress($cartId, $totalsInformation->getAddress());
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod->getErrorMessage() || !$shippingMethod->getAvailable()) {
                continue;
            }

            $item = [];
            $item['id'] = $shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode();
            $item['label'] = $this->addressHelper->getShippingMethodLabel($shippingMethod->getPriceExclTax(), $shippingMethod->getPriceInclTax(), $shippingMethod->getMethodTitle(), $store);
            $item['description'] = $shippingMethod->getCarrierTitle() . ' - ' . $shippingMethod->getMethodTitle();
            $shippingOptions[] = $item;
            if (!$shippingMethodAssigned) {
                $totalsInformation->setShippingCarrierCode($shippingMethod->getCarrierCode());
                $totalsInformation->setShippingMethodCode($shippingMethod->getMethodCode());
                $data['defaultSelectedOption'] = $shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode();
                $shippingMethodAssigned = true;
            }
        }

        if (!empty($shippingOptions)) {
            $data['transactionState'] = 'SUCCESS';
            $data['shippingOptions'] = $shippingOptions;
        } else {
            $this->logger->addError("No shipping method available for the selected address");
            $data['transactionState'] = 'ERROR';
            $data['error'] = [];
            $data['error']['reason'] = 'SHIPPING_ADDRESS_UNSERVICEABLE';
            $data['error']['message'] = 'Cannot ship to the selected address';
            $data['error']['intent'] = 'SHIPPING_ADDRESS';
        }

        return $data;
    }

    /**
     * Get transaction information
     *
     * @param  string                                                $cartId
     * @param  \Magento\Checkout\Api\Data\TotalsInformationInterface $totalsInformation
     * @return array
     */
    protected function getTransactionInfo($cartId, $totalsInformation)
    {
        $totalsInformation = $this->totalsInformationManagement->calculate($cartId, $totalsInformation);
        $transactionInfo = [];
        $transactionInfo['currencyCode'] = $totalsInformation->getQuoteCurrencyCode();
        $transactionInfo['totalPriceStatus'] = 'FINAL';
        $transactionInfo['displayItems'] = [];
        $totalSegments = $totalsInformation->getTotalSegments();
        foreach ($totalSegments as $total) {
            if ($total->getCode() === 'grand_total') {
                $transactionInfo['totalPriceLabel'] = $total->getTitle();
                $transactionInfo['totalPrice'] = number_format($total->getValue(), 2, '.', '');
            } else {
                $item = [];
                if ($total->getCode() === 'subtotal') {
                    $item['type'] = 'SUBTOTAL';
                } else {
                    $item['type'] = 'LINE_ITEM';
                }
                $item['label'] = $total->getTitle();
                $item['price'] = number_format($total->getValue(), 2, '.', '');
                $transactionInfo['displayItems'][] = $item;
            }
        }

        return $transactionInfo;
    }
}
