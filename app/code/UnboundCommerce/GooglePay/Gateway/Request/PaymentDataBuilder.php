<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class PaymentDataBuilder implements BuilderInterface
{
    /**
     * Builds ENV request for payment data
     *
     * @param  array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /**
 * @var PaymentDataObjectInterface $paymentDO 
*/
        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();

        $additionalInfo = $payment->getAdditionalInformation();
        $amount = null;
        if (isset($buildSubject['amount'])) {
            $amount = $buildSubject['amount'];
        } else {
            $amount = $order->getGrandTotalAmount();
        }
        $address = $order->getBillingAddress();

        $res = [
            'amount' => $amount,
            'currency' => $order->getCurrencyCode(),
            'orderId' => $order->getOrderIncrementId(),
            'customerEmail' => $address->getEmail(),
            'firstName' => $address->getFirstname(),
            'lastName' => $address->getLastname(),
            'streetLine1' => $address->getStreetLine1(),
            'streetLine2' => $address->getStreetLine2(),
            'city' => $address->getCity(),
            'region' => $address->getRegionCode(),
            'country' => $address->getCountryId(),
            'postalCode' => $address->getPostcode()
            ];

        if (!empty($additionalInfo)) {
            $res = array_merge($res, $additionalInfo);
        }

        return $res;
    }
}
