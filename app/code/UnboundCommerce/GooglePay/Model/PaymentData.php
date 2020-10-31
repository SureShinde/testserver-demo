<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use UnboundCommerce\GooglePay\Api\Data\PaymentDataInterface;

/**
 * Class PaymentData
 */
class PaymentData extends AbstractExtensibleModel implements PaymentDataInterface
{
    /**
     * {@inheritdoc}
     */
    public function getApiVersion()
    {
        return $this->getData(self::API_VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setApiVersion($apiVersion)
    {
        return $this->setData(self::API_VERSION, $apiVersion);
    }

    /**
     * {@inheritdoc}
     */
    public function getApiVersionMinor()
    {
        return $this->getData(self::API_VERSION_MINOR);
    }

    /**
     * {@inheritdoc}
     */
    public function setApiVersionMinor($apiVersionMinor)
    {
        return $this->setData(self::API_VERSION_MINOR, $apiVersionMinor);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethodData()
    {
        return $this->getData(self::PAYMENT_METHOD_DATA);
    }

    /**
     * {@inheritdoc}
     */
    public function setPaymentMethodData($paymentMethodData)
    {
        return $this->setData(self::PAYMENT_METHOD_DATA, $paymentMethodData);
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail()
    {
        return $this->getData(self::EMAIL);
    }

    /**
     * {@inheritdoc}
     */
    public function setEmail($email)
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingAddress()
    {
        return $this->getData(self::SHIPPING_ADDRESS);
    }

    /**
     * {@inheritdoc}
     */
    public function setShippingAddress($shippingAddress)
    {
        return $this->setData(self::SHIPPING_ADDRESS, $shippingAddress);
    }

    /**
     * {@inheritdoc}
     */
    public function getJsonEncodedString()
    {
        return $this->getData(self::JSON_ENCODED_STRING);
    }

    /**
     * {@inheritdoc}
     */
    public function setJsonEncodedString($jsonString)
    {
        return $this->setData(self::JSON_ENCODED_STRING, $jsonString);
    }
}
