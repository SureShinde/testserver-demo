<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Api\Data;

/**
 * Interface PaymentDataInterface
 *
 * @api
 */
interface PaymentDataInterface
{
    const API_VERSION = 'api_version';
    const API_VERSION_MINOR = 'api_version_minor';
    const PAYMENT_METHOD_DATA = 'payment_method_data';
    const EMAIL = 'email';
    const SHIPPING_ADDRESS = 'shipping_address';
    const JSON_ENCODED_STRING = 'json_encoded_string';

    /**
     * Return major api version
     *
     * @return integer|null
     */
    public function getApiVersion();

    /**
     * Set major api version
     *
     * @param  integer $apiVersion
     * @return $this
     */
    public function setApiVersion($apiVersion);

    /**
     * Return minor api version
     *
     * @return integer|null
     */
    public function getApiVersionMinor();

    /**
     * Set minor api version
     *
     * @param  integer $apiVersionMinor
     * @return $this
     */
    public function setApiVersionMinor($apiVersionMinor);

    /**
     * Return data about the selected payment method
     *
     * @return \UnboundCommerce\GooglePay\Api\Data\PaymentMethodDataInterface
     */
    public function getPaymentMethodData();

    /**
     * Set data about the selected payment method
     *
     * @param  \UnboundCommerce\GooglePay\Api\Data\PaymentMethodDataInterface $paymentMethodData
     * @return $this
     */
    public function setPaymentMethodData($paymentMethodData);

    /**
     * Return email address
     *
     * @return string|null
     */
    public function getEmail();

    /**
     * Set email address
     *
     * @param  string $email
     * @return $this
     */
    public function setEmail($email);

    /**
     * Return shipping address
     *
     * @return \UnboundCommerce\GooglePay\Api\Data\AddressInterface|null
     */
    public function getShippingAddress();

    /**
     * Set shipping address
     *
     * @param  \UnboundCommerce\GooglePay\Api\Data\AddressInterface $shippingAddress
     * @return $this
     */
    public function setShippingAddress($shippingAddress);

    /**
     * Returns paymentData object as json encoded string
     *
     * @return string
     */
    public function getJsonEncodedString();

    /**
     * Sets paymentData object as json encoded string
     *
     * @param  string $jsonString
     * @return $this
     */
    public function setJsonEncodedString($jsonString);
}
